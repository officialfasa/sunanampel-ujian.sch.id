<?php
session_start();
require_once 'koneksi.php';

// Cek Hak Akses Admin / Guru
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher')) {
    header("Location: index.php");
    exit();
}

$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$role      = $_SESSION['role'];

// Statistik Utama
$total_siswa  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='student' AND is_approved=1"))['total'];
$total_guru   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='teacher'"))['total'];
$total_ujian  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM exams"))['total'];
$total_online = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(DISTINCT user_id) as total FROM activity_logs WHERE created_at >= NOW() - INTERVAL 15 MINUTE AND activity_type != 'LOGOUT'"))['total'];

// Query Kelola Ujian
$query_exams = "SELECT e.*, 
                (SELECT COUNT(*) FROM exam_sessions es WHERE es.exam_id = e.id AND es.status = 'completed') as total_selesai 
                FROM exams e ORDER BY e.id DESC";
$result_exams = mysqli_query($koneksi, $query_exams);

// Query Pengguna Online
$query_online = "SELECT u.full_name, u.role, u.school_level, u.grade_level, MAX(al.created_at) as last_active 
                 FROM activity_logs al 
                 JOIN users u ON al.user_id = u.id 
                 WHERE al.created_at >= NOW() - INTERVAL 15 MINUTE AND al.activity_type != 'LOGOUT'
                 GROUP BY u.id ORDER BY last_active DESC";
$result_online = mysqli_query($koneksi, $query_online);

// Query Siswa Belum Login
$query_unlogged = "SELECT * FROM users WHERE role = 'student' AND id NOT IN (SELECT DISTINCT user_id FROM activity_logs WHERE activity_type = 'LOGIN') ORDER BY school_level ASC, grade_level ASC, full_name ASC";
$result_unlogged = mysqli_query($koneksi, $query_unlogged);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pusat Kendali Admin - Portal Ujian MI/MTs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f6f9; }
        .navbar-admin { background: linear-gradient(45deg, #0d6efd, #0dcaf0); }
        .nav-tabs .nav-link { color: #495057; font-weight: 600; border-radius: 10px 10px 0 0; }
        .nav-tabs .nav-link.active { background-color: #fff; color: #0d6efd; border-bottom: 3px solid #0d6efd; }
        .card-stat { border: none; border-radius: 15px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-admin shadow-sm py-3">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="#"><i class="fa-solid fa-user-gear me-2"></i>SUPER PANEL ADMIN UJIAN</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3"><i class="fa-solid fa-circle-user me-1"></i> <?= $full_name; ?> (<?= strtoupper($role); ?>)</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill"><i class="fa-solid fa-right-from-bracket me-1"></i> Keluar</a>
        </div>
    </div>
</nav>

<div class="container-fluid my-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold text-dark m-0">Pusat Kendali Utama Admin</h3>
            <p class="text-muted m-0">Kelola Ujian, Verifikasi Siswa, Manajemen Guru, & Rekapan Nilai.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="kelola_guru.php" class="btn btn-outline-success btn-lg rounded-pill fw-bold shadow-sm">
                <i class="fa-solid fa-chalkboard-user me-2"></i>Kelola Guru
            </a>
            <a href="kelola_siswa.php" class="btn btn-outline-primary btn-lg rounded-pill fw-bold shadow-sm">
                <i class="fa-solid fa-user-plus me-2"></i>Kelola / Import Siswa
            </a>
            <a href="buat_ujian.php" class="btn btn-primary btn-lg rounded-pill fw-bold shadow">
                <i class="fa-solid fa-plus-circle me-2"></i>Buat Ujian / Import LaTeX
            </a>
        </div>
    </div>

    <!-- Statistik Ringkas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card card-stat bg-primary text-white p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="text-white-50 m-0">SISWA AKTIF</h6><h2 class="fw-bold m-0"><?= $total_siswa; ?></h2></div>
                    <i class="fa-solid fa-users fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat bg-success text-white p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="text-white-50 m-0">GURU TERDAFTAR</h6><h2 class="fw-bold m-0"><?= $total_guru; ?></h2></div>
                    <i class="fa-solid fa-chalkboard-user fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat bg-warning text-dark p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="text-dark-50 m-0">TOTAL UJIAN</h6><h2 class="fw-bold m-0"><?= $total_ujian; ?></h2></div>
                    <i class="fa-solid fa-file-signature fa-3x text-dark-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat bg-info text-white p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h6 class="text-white-50 m-0">SISWA ONLINE</h6><h2 class="fw-bold m-0"><?= $total_online; ?></h2></div>
                    <i class="fa-solid fa-signal fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB NAVIGASI -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white pt-3 px-4 border-0">
            <ul class="nav nav-tabs card-header-tabs" id="adminTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="ujian-tab" data-bs-toggle="tab" data-bs-target="#ujian" type="button"><i class="fa-solid fa-list-check me-2"></i>Kelola Ujian & Pantau Real-Time</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="riwayat-tab" data-bs-toggle="tab" data-bs-target="#riwayat" type="button"><i class="fa-solid fa-clock-rotate-left me-2 text-warning"></i>Riwayat & Hasil Ujian</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="online-tab" data-bs-toggle="tab" data-bs-target="#online" type="button"><i class="fa-solid fa-user-check me-2 text-success"></i>Siswa Sedang Online</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="unlogged-tab" data-bs-toggle="tab" data-bs-target="#unlogged" type="button"><i class="fa-solid fa-user-xmark me-2 text-danger"></i>Siswa Belum Login</button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content" id="adminTabContent">
                
                <!-- TAB 1: KELOLA UJIAN -->
                <div class="tab-pane fade show active" id="ujian">
                    <h5 class="fw-bold mb-3">Daftar Ujian (Edit, Pantau Progress, & Hapus)</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Judul Ujian</th>
                                    <th>Kategori</th>
                                    <th>Target Jenjang</th>
                                    <th>Durasi</th>
                                    <th>Selesai Ujian</th>
                                    <th class="text-center">Aksi / Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result_exams) > 0): ?>
                                    <?php while ($exam = mysqli_fetch_assoc($result_exams)): ?>
                                        <tr>
                                            <td class="fw-bold"><?= $exam['title']; ?></td>
                                            <td><span class="badge bg-secondary"><?= strtoupper($exam['category']); ?></span></td>
                                            <td><span class="badge bg-info text-dark"><?= $exam['school_level']; ?></span></td>
                                            <td><?= $exam['duration_minutes']; ?> Menit</td>
                                            <td><span class="badge bg-success"><?= $exam['total_selesai']; ?> Siswa</span></td>
                                            <td class="text-center">
                                                <a href="kelola_soal.php?exam_id=<?= $exam['id']; ?>" class="btn btn-primary btn-sm rounded-pill me-1"><i class="fa-solid fa-list-ol me-1"></i> Kelola Soal</a>
                                                <a href="pantau_ujian.php?id=<?= $exam['id']; ?>" class="btn btn-warning btn-sm rounded-pill me-1 text-dark fw-bold"><i class="fa-solid fa-eye me-1"></i> Pantau Live</a>
                                                <a href="edit_ujian.php?id=<?= $exam['id']; ?>" class="btn btn-info btn-sm text-white rounded-pill me-1"><i class="fa-solid fa-pen me-1"></i> Edit Paket</a>
                                                <a href="hapus_ujian.php?id=<?= $exam['id']; ?>" class="btn btn-danger btn-sm rounded-pill" onclick="return confirm('Hapus ujian ini secara permanen?')"><i class="fa-solid fa-trash me-1"></i> Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-3 text-muted">Belum ada ujian dibuat.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: RIWAYAT & HASIL UJIAN (PER EVENT / PER UJIAN) -->
                <div class="tab-pane fade" id="riwayat">
                    <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-clock-rotate-left me-2"></i>Riwayat Ujian Per Event / Paket Ujian</h5>
                    <p class="text-muted small">Pilih paket ujian di bawah ini untuk melihat detail lengkap pengerjaan peserta (Nilai, Benar, Salah, Ragu, Kosong, & Waktu) serta Ekspor Hasil.</p>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Ujian / Event</th>
                                    <th>Kategori</th>
                                    <th>Target Jenjang</th>
                                    <th>Peserta Selesai</th>
                                    <th>Jadwal Ujian</th>
                                    <th class="text-center">Laporan & Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $result_exams_riwayat = mysqli_query($koneksi, $query_exams);
                                if (mysqli_num_rows($result_exams_riwayat) > 0): 
                                    $no = 1;
                                    while ($ex = mysqli_fetch_assoc($result_exams_riwayat)): 
                                ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td class="fw-bold"><?= $ex['title']; ?></td>
                                        <td><span class="badge bg-secondary"><?= strtoupper($ex['category']); ?></span></td>
                                        <td><span class="badge bg-info text-dark"><?= $ex['school_level']; ?></span></td>
                                        <td><span class="badge bg-success"><?= $ex['total_selesai']; ?> Siswa</span></td>
                                        <td><small class="text-muted"><?= date('d M Y, H:i', strtotime($ex['start_time'])); ?> WIB</small></td>
                                        <td class="text-center">
                                            <a href="detail_riwayat.php?id=<?= $ex['id']; ?>" class="btn btn-primary btn-sm rounded-pill fw-bold px-3">
                                                <i class="fa-solid fa-chart-line me-1"></i> Lihat Detail & Ekspor Hasil
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="7" class="text-center py-3 text-muted">Belum ada riwayat ujian/event yang dibuat.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: USER ONLINE -->
                <div class="tab-pane fade" id="online">
                    <h5 class="fw-bold mb-3 text-success"><i class="fa-solid fa-circle text-success me-2"></i>Daftar Pengguna Sedang Online (Aktif)</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Pengguna</th>
                                    <th>Role</th>
                                    <th>Jenjang / Kelas</th>
                                    <th>Aktivitas Terakhir</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result_online) > 0): ?>
                                    <?php while ($on = mysqli_fetch_assoc($result_online)): ?>
                                        <tr>
                                            <td class="fw-bold"><?= $on['full_name']; ?></td>
                                            <td><span class="badge bg-primary"><?= strtoupper($on['role']); ?></span></td>
                                            <td><?= $on['school_level'] ? $on['school_level'].' Kelas '.$on['grade_level'] : '-'; ?></td>
                                            <td><?= $on['last_active']; ?></td>
                                            <td><span class="badge bg-success"><i class="fa-solid fa-signal me-1"></i>ONLINE</span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-3 text-muted">Tidak ada pengguna yang sedang aktif online.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 4: SISWA BELUM LOGIN -->
                <div class="tab-pane fade" id="unlogged">
                    <h5 class="fw-bold mb-3 text-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>Daftar Siswa Belum Pernah Login</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>NISN</th>
                                    <th>Nama Siswa</th>
                                    <th>Jenjang</th>
                                    <th>Kelas</th>
                                    <th>Status Login</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result_unlogged) > 0): ?>
                                    <?php while ($un = mysqli_fetch_assoc($result_unlogged)): ?>
                                        <tr>
                                            <td><code><?= $un['nisn_or_nip']; ?></code></td>
                                            <td class="fw-bold"><?= $un['full_name']; ?></td>
                                            <td><span class="badge bg-warning text-dark"><?= $un['school_level']; ?></span></td>
                                            <td>Kelas <?= $un['grade_level']; ?></td>
                                            <td><span class="badge bg-danger">Belum Pernah Login</span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-3 text-success fw-bold">Semua siswa sudah pernah login ke sistem!</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>