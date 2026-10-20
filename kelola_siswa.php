<?php
session_start();
require_once 'koneksi.php';

// Cek Hak Akses Admin / Guru
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher')) {
    header("Location: index.php");
    exit();
}

$message = "";

// 1. AKSI ADMIN (VERIFIKASI / EDIT / HAPUS)
if (isset($_GET['action'])) {
    $uid = (int)$_GET['uid'];
    if ($_GET['action'] == 'approve') {
        mysqli_query($koneksi, "UPDATE users SET is_approved = 1 WHERE id = '$uid'");
        $message = "Akun siswa berhasil disetujui / diverifikasi!";
    } elseif ($_GET['action'] == 'reject') {
        mysqli_query($koneksi, "UPDATE users SET is_approved = 2 WHERE id = '$uid'");
        $message = "Pendaftaran siswa telah ditolak!";
    } elseif ($_GET['action'] == 'delete') {
        mysqli_query($koneksi, "DELETE FROM users WHERE id = '$uid'");
        $message = "Data siswa berhasil dihapus!";
    }
}

// 2. SIMPAN EDIT PROFIL SISWA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_student'])) {
    $uid          = (int)$_POST['user_id'];
    $full_name    = mysqli_real_escape_string($koneksi, $_POST['full_name']);
    $nisn         = mysqli_real_escape_string($koneksi, $_POST['nisn']);
    $username     = mysqli_real_escape_string($koneksi, $_POST['username']);
    $school_level = $_POST['school_level'];
    $grade_level  = (int)$_POST['grade_level'];

    $update_sql = "UPDATE users SET full_name='$full_name', nisn_or_nip='$nisn', username='$username', school_level='$school_level', grade_level='$grade_level'";
    
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $update_sql .= ", password='$password'";
    }
    
    $update_sql .= " WHERE id='$uid'";
    
    mysqli_query($koneksi, $update_sql);
    $message = "Profil siswa berhasil diperbarui!";
}

// 3. TAMBAH SISWA MANUAL BY ADMIN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_type']) && $_POST['action_type'] == 'manual') {
    $full_name    = mysqli_real_escape_string($koneksi, trim($_POST['full_name']));
    $nisn         = mysqli_real_escape_string($koneksi, trim($_POST['nisn']));
    $username     = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password     = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $school_level = $_POST['school_level'];
    $grade_level  = (int)$_POST['grade_level'];

    // Cek Duplikat NISN / Username
    $check_exist = mysqli_query($koneksi, "SELECT id FROM users WHERE nisn_or_nip = '$nisn' OR username = '$username'");
    if (mysqli_num_rows($check_exist) > 0) {
        $message = "Gagal! NISN atau Username sudah pernah terdaftar.";
    } else {
        mysqli_query($koneksi, "INSERT INTO users (username, password, full_name, nisn_or_nip, role, school_level, grade_level, is_approved) 
                                VALUES ('$username', '$password', '$full_name', '$nisn', 'student', '$school_level', '$grade_level', 1)");
        $message = "Siswa manual berhasil ditambahkan dan langsung aktif!";
    }
}

// 4. IMPORT SISWA VIA EXCEL / CSV
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_type']) && $_POST['action_type'] == 'import_excel') {
    if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == 0) {
        $filename = $_FILES['file_csv']['tmp_name'];
        $handle   = fopen($filename, "r");
        
        // Lewati baris pertama (header)
        fgetcsv($handle, 1000, ",");
        
        $imported_count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (!empty($data[0])) {
                $nisn         = mysqli_real_escape_string($koneksi, trim($data[0]));
                $full_name    = mysqli_real_escape_string($koneksi, trim($data[1]));
                $username     = mysqli_real_escape_string($koneksi, trim($data[2]));
                $raw_pass     = !empty($data[3]) ? $data[3] : '123456';
                $password     = password_hash($raw_pass, PASSWORD_DEFAULT);
                $school_level = mysqli_real_escape_string($koneksi, trim($data[4]));
                $grade_level  = (int)$data[5];

                $check_exist = mysqli_query($koneksi, "SELECT id FROM users WHERE nisn_or_nip = '$nisn' OR username = '$username'");
                if (mysqli_num_rows($check_exist) == 0) {
                    mysqli_query($koneksi, "INSERT INTO users (username, password, full_name, nisn_or_nip, role, school_level, grade_level, is_approved) 
                                            VALUES ('$username', '$password', '$full_name', '$nisn', 'student', '$school_level', '$grade_level', 1)");
                    $imported_count++;
                }
            }
        }
        fclose($handle);
        $message = "Berhasil mengimpor $imported_count data siswa dari CSV/Excel!";
    }
}

// Ambil Seluruh Data Siswa
$students = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'student' ORDER BY is_approved ASC, school_level ASC, grade_level ASC, full_name ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Peserta & Import Siswa - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; } </style>
</head>
<body class="p-4">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0"><i class="fa-solid fa-users-gear text-primary me-2"></i>Pusat Kelola & Verifikasi Peserta Ujian</h3>
        <a href="admin_dashboard.php" class="btn btn-secondary rounded-pill"><i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info alert-dismissible fade show fw-bold rounded-3" role="alert">
            <i class="fa-solid fa-circle-info me-1"></i> <?= $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- SISIKAN KIRI: FORM INPUT MANUAL & IMPORT EXCEL/CSV -->
        <div class="col-lg-4">
            
            <!-- FORM 1: INPUT MANUAL SISWA -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
                <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-user-plus me-2"></i>Tambah Siswa Manual</h5>
                <form action="" method="POST">
                    <input type="hidden" name="action_type" value="manual">
                    <div class="mb-2">
                        <label class="small fw-bold">Nama Lengkap Siswa</label>
                        <input type="text" name="full_name" class="form-control" placeholder="Nama Lengkap" required>
                    </div>
                    <div class="mb-2">
                        <label class="small fw-bold">NISN</label>
                        <input type="text" name="nisn" class="form-control" placeholder="Nomor NISN" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="small fw-bold">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Username" required>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Password" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="small fw-bold">Jenjang</label>
                            <select name="school_level" class="form-select">
                                <option value="MI">MI</option>
                                <option value="MTs">MTs</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold">Kelas</label>
                            <input type="number" name="grade_level" class="form-control" placeholder="1-9" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm">
                        <i class="fa-solid fa-save me-1"></i> Simpan & Aktifkan Siswa
                    </button>
                </form>
            </div>

            <!-- FORM 2: IMPORT MASSAL VIA CSV / EXCEL -->
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <h5 class="fw-bold text-success mb-3"><i class="fa-solid fa-file-csv me-2"></i>Import Siswa via CSV / Excel</h5>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action_type" value="import_excel">
                    <div class="mb-3">
                        <label class="small fw-bold">Pilih File CSV/Excel (.csv)</label>
                        <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                        <small class="text-muted d-block mt-1">Format kolom CSV: <code>nisn, full_name, username, password, school_level, grade_level</code></small>
                    </div>
                    <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold shadow-sm">
                        <i class="fa-solid fa-file-import me-1"></i> Import Banyak Siswa
                    </button>
                </form>
            </div>

        </div>

        <!-- SISIKAN KANAN: TABEL REKAPAN MASTER SELURUH PESERTA -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-list me-2 text-primary"></i>Daftar Master Seluruh Peserta</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>NISN</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Jenjang/Kelas</th>
                                <th>Status</th>
                                <th class="text-center">Aksi Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($students) > 0): ?>
                                <?php while ($s = mysqli_fetch_assoc($students)): ?>
                                    <tr>
                                        <td><code><?= $s['nisn_or_nip']; ?></code></td>
                                        <td class="fw-bold"><?= $s['full_name']; ?></td>
                                        <td><small><?= $s['username']; ?></small></td>
                                        <td><span class="badge bg-info text-dark"><?= $s['school_level']; ?> - Kelas <?= $s['grade_level']; ?></span></td>
                                        <td>
                                            <?php if ($s['is_approved'] == 1): ?>
                                                <span class="badge bg-success">Disetujui / Aktif</span>
                                            <?php elseif ($s['is_approved'] == 0): ?>
                                                <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Ditolak</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <!-- Button Edit Profil -->
                                            <button class="btn btn-primary btn-sm rounded-pill me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $s['id']; ?>"><i class="fa-solid fa-pen"></i> Edit</button>
                                            
                                            <!-- Verifikasi Pendaftaran Mandiri -->
                                            <?php if ($s['is_approved'] == 0): ?>
                                                <a href="kelola_siswa.php?action=approve&uid=<?= $s['id']; ?>" class="btn btn-success btn-sm rounded-pill me-1"><i class="fa-solid fa-check"></i> Setujui</a>
                                                <a href="kelola_siswa.php?action=reject&uid=<?= $s['id']; ?>" class="btn btn-warning btn-sm text-dark rounded-pill me-1"><i class="fa-solid fa-xmark"></i> Tolak</a>
                                            <?php endif; ?>

                                            <a href="kelola_siswa.php?action=delete&uid=<?= $s['id']; ?>" class="btn btn-danger btn-sm rounded-pill" onclick="return confirm('Hapus siswa ini secara permanen?')"><i class="fa-solid fa-trash"></i> Hapus</a>
                                        </td>
                                    </tr>

                                    <!-- MODAL EDIT PROFIL SISWA -->
                                    <div class="modal fade" id="editModal<?= $s['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content rounded-4">
                                                <form action="" method="POST">
                                                    <input type="hidden" name="edit_student" value="1">
                                                    <input type="hidden" name="user_id" value="<?= $s['id']; ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Edit Profil Siswa</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-2"><label class="fw-bold small">Nama Lengkap</label><input type="text" name="full_name" class="form-control" value="<?= $s['full_name']; ?>" required></div>
                                                        <div class="mb-2"><label class="fw-bold small">NISN</label><input type="text" name="nisn" class="form-control" value="<?= $s['nisn_or_nip']; ?>" required></div>
                                                        <div class="row g-2 mb-2">
                                                            <div class="col-6"><label class="fw-bold small">Username</label><input type="text" name="username" class="form-control" value="<?= $s['username']; ?>" required></div>
                                                            <div class="col-6"><label class="fw-bold small">Ganti Password (Opsional)</label><input type="password" name="password" class="form-control" placeholder="Biarkan kosong jika tidak diubah"></div>
                                                        </div>
                                                        <div class="row g-2">
                                                            <div class="col-6"><label class="fw-bold small">Jenjang</label><select name="school_level" class="form-select"><option value="MI" <?= $s['school_level']=='MI'?'selected':''; ?>>MI</option><option value="MTs" <?= $s['school_level']=='MTs'?'selected':''; ?>>MTs</option></select></div>
                                                            <div class="col-6"><label class="fw-bold small">Kelas</label><input type="number" name="grade_level" class="form-control" value="<?= $s['grade_level']; ?>" required></div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer"><button type="submit" class="btn btn-primary rounded-pill fw-bold">Simpan Profil</button></div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-3 text-muted">Belum ada data siswa terdaftar.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>