<?php
require_once '../includes/config.php';
requireRole('teacher');
$tid  = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$sid  = intval($data['syllabus_id']);
$st   = intval($data['status']);

$conn->query("INSERT INTO topic_done_status (teacher_id, syllabus_id, status, done_at)
    VALUES ($tid, $sid, $st, NOW())
    ON DUPLICATE KEY UPDATE status=$st, done_at=NOW()");

echo json_encode(['success' => true]);
?>