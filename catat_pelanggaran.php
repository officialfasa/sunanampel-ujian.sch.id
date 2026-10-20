<?php
session_start();
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $exam_session_id = (int)$_POST['exam_session_id'];
    $reason          = mysqli_real_escape_string($koneksi, $_POST['reason']);

    // Update status sesi menjadi dicurangi dan tambah hitungan pelanggaran
    $query = "UPDATE exam_sessions 
              SET status = 'completed', 
                  violation_count = violation_count + 1, 
                  violation_reason = '$reason' 
              WHERE id = '$exam_session_id'";
    
    if (mysqli_query($koneksi, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($koneksi)]);
    }
}
?>