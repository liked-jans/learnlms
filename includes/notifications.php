<?php
/**
 * BlendEd LMS - Real-time Notifications & Deadline Helper
 * Supports Research Objective 2: Timely alerts and announcements
 */

function getStudentNotifications($studentId) {
    global $conn;
    $notifs = [
        'due_soon' => [],
        'recent_grades' => [],
        'unread_count' => 0
    ];

    // 1. Upcoming assessments due in the next 7 days that haven't been submitted
    $stmt = $conn->prepare("
        SELECT a.id, a.title, a.type, a.due_date, a.max_score, c.course_code, c.course_name
        FROM assessments a
        JOIN syllabi s ON a.syllabus_id = s.id
        JOIN courses c ON s.course_id = c.id
        JOIN enrollments e ON e.syllabus_id = s.id
        LEFT JOIN submissions sub ON sub.assessment_id = a.id AND sub.student_id = ?
        WHERE e.student_id = ? 
          AND e.status = 'enrolled'
          AND s.status = 'published'
          AND sub.id IS NULL
          AND a.due_date IS NOT NULL
          AND a.due_date >= NOW()
          AND a.due_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
        ORDER BY a.due_date ASC
        LIMIT 5
    ");
    $stmt->bind_param('ii', $studentId, $studentId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $hoursLeft = round((strtotime($row['due_date']) - time()) / 3600);
        $row['hours_left'] = $hoursLeft;
        $row['urgency'] = $hoursLeft <= 24 ? 'danger' : ($hoursLeft <= 48 ? 'warning' : 'info');
        $notifs['due_soon'][] = $row;
    }

    // 2. Submissions graded in the last 7 days with feedback
    $stmt2 = $conn->prepare("
        SELECT sub.id, sub.score, sub.feedback, sub.graded_at, a.title, a.max_score, c.course_code
        FROM submissions sub
        JOIN assessments a ON sub.assessment_id = a.id
        JOIN syllabi s ON a.syllabus_id = s.id
        JOIN courses c ON s.course_id = c.id
        WHERE sub.student_id = ?
          AND sub.status = 'graded'
          AND sub.graded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY sub.graded_at DESC
        LIMIT 5
    ");
    $stmt2->bind_param('i', $studentId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($row2 = $res2->fetch_assoc()) {
        $notifs['recent_grades'][] = $row2;
    }

    $notifs['unread_count'] = count($notifs['due_soon']) + count($notifs['recent_grades']);
    return $notifs;
}

function getTeacherNotifications($teacherId) {
    global $conn;
    $notifs = [
        'pending_grading' => [],
        'ungraded_count' => 0
    ];

    // Assessments with submissions pending review
    $stmt = $conn->prepare("
        SELECT a.id as assessment_id, a.title, a.max_score, c.course_code, COUNT(sub.id) as pending_count
        FROM assessments a
        JOIN syllabi s ON a.syllabus_id = s.id
        JOIN courses c ON s.course_id = c.id
        JOIN submissions sub ON sub.assessment_id = a.id
        WHERE a.teacher_id = ?
          AND (sub.status = 'submitted' OR sub.status IS NULL OR sub.score IS NULL)
        GROUP BY a.id
        ORDER BY pending_count DESC
        LIMIT 5
    ");
    $stmt->bind_param('i', $teacherId);
    $stmt->execute();
    $res = $stmt->get_result();
    $total = 0;
    while ($row = $res->fetch_assoc()) {
        $total += (int)$row['pending_count'];
        $notifs['pending_grading'][] = $row;
    }
    $notifs['ungraded_count'] = $total;
    return $notifs;
}
?>
