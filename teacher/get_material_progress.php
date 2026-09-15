<?php
require_once __DIR__ . '/../includes/config.php';
requireRole('teacher');
header('Content-Type: application/json');

$tid = (int)$_SESSION['user_id'];
$matId = (int)($_GET['id'] ?? 0);

if ($matId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid material ID']);
    exit();
}

// Fetch material and verify it belongs to this teacher
$stmt = $conn->prepare("
    SELECT m.id, m.syllabus_id, m.syllabus_topic_id, m.title, m.type, m.estimated_read_time,
           c.course_code, c.course_name, st.topic_title, st.week_number
    FROM learning_materials m
    JOIN syllabi s ON m.syllabus_id = s.id
    JOIN courses c ON s.course_id = c.id
    LEFT JOIN syllabus_topics st ON m.syllabus_topic_id = st.id
    WHERE m.id = ? AND m.teacher_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $matId, $tid);
$stmt->execute();
$mat = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mat) {
    echo json_encode(['success' => false, 'error' => 'Material not found or access denied']);
    exit();
}

// If no topic is linked, there's no syllabus_topic_id in topic_progress
if (empty($mat['syllabus_topic_id'])) {
    $eq = $conn->prepare("
        SELECT u.id as student_id, u.full_name, u.email
        FROM enrollments e
        JOIN users u ON e.student_id = u.id
        WHERE e.syllabus_id = ? AND e.status = 'enrolled'
        ORDER BY u.full_name ASC
    ");
    $eq->bind_param('i', $mat['syllabus_id']);
    $eq->execute();
    $studentsRaw = $eq->get_result()->fetch_all(MYSQLI_ASSOC);
    $eq->close();

    $students = [];
    foreach ($studentsRaw as $s) {
        $students[] = [
            'student_id' => (int)$s['student_id'],
            'full_name' => $s['full_name'],
            'email' => $s['email'],
            'read_pct' => 0.0,
            'status' => 'not_started',
            'status_label' => 'Not Started',
            'last_read_formatted' => 'Not started yet'
        ];
    }

    echo json_encode([
        'success' => true,
        'material' => $mat,
        'summary' => [
            'total_enrolled' => count($students),
            'finished_count' => 0,
            'reading_count' => 0,
            'not_started_count' => count($students),
            'avg_pct' => 0.0
        ],
        'students' => $students
    ]);
    exit();
}

// Query all enrolled students and their progress on this topic
$q = $conn->prepare("
    SELECT u.id as student_id, u.full_name, u.email,
           COALESCE(tp.read_percentage, 0.00) as read_pct,
           COALESCE(tp.status, 'not_started') as status,
           tp.last_read_at, tp.completed_at
    FROM enrollments e
    JOIN users u ON e.student_id = u.id
    LEFT JOIN topic_progress tp ON tp.syllabus_topic_id = ? AND tp.student_id = e.student_id
    WHERE e.syllabus_id = ? AND e.status = 'enrolled'
    ORDER BY read_pct DESC, u.full_name ASC
");
$q->bind_param('ii', $mat['syllabus_topic_id'], $mat['syllabus_id']);
$q->execute();
$raw = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();

$totalEnrolled = count($raw);
$finishedCount = 0;
$readingCount = 0;
$notStartedCount = 0;
$sumPct = 0.0;
$students = [];

foreach ($raw as $r) {
    $pct = (float)$r['read_pct'];
    $sumPct += $pct;

    if ($pct >= 90.0 || $r['status'] === 'completed') {
        $statusKey = 'completed';
        $statusLabel = 'Finished';
        $finishedCount++;
    } elseif ($pct > 0.0) {
        $statusKey = 'in_progress';
        $statusLabel = 'Reading';
        $readingCount++;
    } else {
        $statusKey = 'not_started';
        $statusLabel = 'Not Started';
        $notStartedCount++;
    }

    $lastRead = !empty($r['last_read_at']) ? date('M j, Y g:i A', strtotime($r['last_read_at'])) : 'Not started yet';

    $students[] = [
        'student_id' => (int)$r['student_id'],
        'full_name' => $r['full_name'],
        'email' => $r['email'],
        'read_pct' => round($pct, 1),
        'status' => $statusKey,
        'status_label' => $statusLabel,
        'last_read_formatted' => $lastRead
    ];
}

$avgPct = $totalEnrolled > 0 ? round($sumPct / $totalEnrolled, 1) : 0.0;

echo json_encode([
    'success' => true,
    'material' => [
        'id' => (int)$mat['id'],
        'title' => $mat['title'],
        'type' => $mat['type'],
        'estimated_read_time' => (int)($mat['estimated_read_time'] ?: 5),
        'course_code' => $mat['course_code'],
        'course_name' => $mat['course_name'],
        'week_number' => $mat['week_number'],
        'topic_title' => $mat['topic_title']
    ],
    'summary' => [
        'total_enrolled' => $totalEnrolled,
        'finished_count' => $finishedCount,
        'reading_count' => $readingCount,
        'not_started_count' => $notStartedCount,
        'avg_pct' => $avgPct
    ],
    'students' => $students
]);
