<?php
require_once '../includes/config.php';
requireRole('teacher');
$tid  = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$topicId = intval($data['topic_id']);
$st      = intval($data['status']);

$conn->query("INSERT INTO topic_week_done (teacher_id, topic_id, status, done_at)
    VALUES ($tid, $topicId, $st, NOW())
    ON DUPLICATE KEY UPDATE status=$st, done_at=NOW()");

echo json_encode(['success' => true]);
?>