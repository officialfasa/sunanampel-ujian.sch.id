<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exam_session_id    = (int)$_POST['exam_session_id'];
    $question_id        = (int)$_POST['question_id'];
    $selected_option_id = !empty($_POST['selected_option_id']) ? (int)$_POST['selected_option_id'] : 'NULL';
    $answer_status      = mysqli_real_escape_string($koneksi, $_POST['answer_status'] ?? 'yakin');

    // Cek apakah siswa sudah pernah menjawab
    $check_query = "SELECT id FROM student_answers WHERE exam_session_id = '$exam_session_id' AND question_id = '$question_id'";
    $check_res   = mysqli_query($koneksi, $check_query);

    if (mysqli_num_rows($check_res) > 0) {
        $query = "UPDATE student_answers 
                  SET selected_option_id = $selected_option_id, answer_status = '$answer_status', updated_at = NOW() 
                  WHERE exam_session_id = '$exam_session_id' AND question_id = '$question_id'";
    } else {
        $query = "INSERT INTO student_answers (exam_session_id, question_id, selected_option_id, answer_status) 
                  VALUES ('$exam_session_id', '$question_id', $selected_option_id, '$answer_status')";
    }

    if (mysqli_query($koneksi, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($koneksi)]);
    }
}
?>