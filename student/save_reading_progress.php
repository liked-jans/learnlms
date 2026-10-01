<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'student') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody, true);
if (!$input || !is_array($input)) {
    $input = !empty($_POST) ? $_POST : $_GET;
}

$stid = (int)$_SESSION['user_id'];
$topicId = (int)($input['topic_id'] ?? 0);
$matId = (int)($input['material_id'] ?? 0);
$percentage = min(100.00, max(0.00, (float)($input['percentage'] ?? 0.00)));

if ($topicId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Missing topic ID']);
    exit();
}

// Upsert into topic_progress
$isCompleted = ($percentage >= 90.0) ? 1 : 0;
$status = ($percentage >= 90.0) ? 'completed' : 'in_progress';

$stmt = $conn->prepare("
    INSERT INTO topic_progress (student_id, syllabus_topic_id, status, read_percentage, last_read_at, completed_at)
    VALUES (?, ?, ?, ?, NOW(), IF(? = 1, NOW(), NULL))
    ON DUPLICATE KEY UPDATE
        read_percentage = GREATEST(COALESCE(read_percentage, 0), VALUES(read_percentage)),
        status = IF(GREATEST(COALESCE(read_percentage, 0), VALUES(read_percentage)) >= 90 OR status = 'completed', 'completed', 'in_progress'),
        last_read_at = NOW(),
        completed_at = IF(completed_at IS NULL AND (VALUES(status) = 'completed' OR status = 'completed'), NOW(), completed_at)
");
$stmt->bind_param('iisdi', $stid, $topicId, $status, $percentage, $isCompleted);
$stmt->execute();

echo json_encode([
    'success' => true,
    'percentage' => $percentage,
    'completed' => ($percentage >= 90.0)
]);
