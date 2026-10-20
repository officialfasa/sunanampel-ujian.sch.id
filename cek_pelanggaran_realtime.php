<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher')) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit();
}

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// Query mengambil peserta yang terdeteksi melakukan pelanggaran
$query = "SELECT es.*, u.full_name, u.username, u.school_level, g.grade_name
          FROM exam_sessions es
          JOIN users u ON es.student_id = u.id
          LEFT JOIN grades g ON u.grade_level = g.id
          WHERE es.exam_id = '$exam_id' 
            AND es.violation_count > 0 
          ORDER BY es.id DESC";

$result = mysqli_query($koneksi, $query);
$violations = [];

while ($row = mysqli_fetch_assoc($result)) {
    $violations[] = [
        'session_id'       => $row['id'],
        'student_name'     => $row['full_name'] ?: $row['username'],
        'grade_info'       => $row['school_level'] . ' Kelas ' . ($row['grade_name'] ?? $row['grade_level']),
        'violation_reason' => $row['violation_reason'],
        'violation_count'  => $row['violation_count']
    ];
}

echo json_encode([
    'status' => 'success',
    'total_violations' => count($violations),
    'data' => $violations
]);
?>