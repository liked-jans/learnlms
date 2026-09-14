<?php
require_once '../includes/config.php';
requireRole('teacher');
$sylId = (int)($_GET['syl'] ?? 0);
$tid = $_SESSION['user_id'];
$result = $conn->query("SELECT st.id, st.week_number, st.topic_title FROM syllabus_topics st JOIN syllabi s ON st.syllabus_id=s.id WHERE st.syllabus_id=$sylId AND s.teacher_id=$tid ORDER BY st.week_number");
$out = [];
while($r=$result->fetch_assoc()) $out[]=$r;
header('Content-Type: application/json');
echo json_encode($out);
