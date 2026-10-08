<?php
require_once '../includes/config.php';
requireRole('teacher');
$sylId = (int)($_GET['syl'] ?? 0);
$tid = $_SESSION['user_id'];
$result = $conn->query("
    SELECT st.id, st.week_number, st.topic_title, st.is_completed 
    FROM syllabus_topics st 
    JOIN syllabi s ON st.syllabus_id=s.id 
    WHERE st.syllabus_id=$sylId AND s.teacher_id=$tid 
    ORDER BY st.week_number ASC, st.sort_order ASC
");
$out = [];
while($r = $result->fetch_assoc()) {
    $wk = (int)$r['week_number'];
    $check = checkPastWeeklySyllabiDone($sylId, (int)$r['id'], $wk);
    $out[] = [
        'id' => (int)$r['id'],
        'week_number' => $wk,
        'topic_title' => $r['topic_title'],
        'is_completed' => (int)($r['is_completed'] ?? 0),
        'can_proceed' => $check['can_proceed'],
        'missing_weeks' => $check['missing_weeks'] ?? [],
        'lock_message' => !$check['can_proceed'] ? $check['message'] : ''
    ];
}
header('Content-Type: application/json');
echo json_encode($out);
