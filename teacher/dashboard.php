<?php
require_once '../includes/config.php';
requireRole('teacher');
$pageTitle = 'Teacher Dashboard';
$tid = $_SESSION['user_id'];

$mySyllabi = $conn->query("SELECT COUNT(*) c FROM syllabi WHERE teacher_id=$tid")->fetch_assoc()['c'];
$myStudents = $conn->query("SELECT COUNT(DISTINCT e.student_id) c FROM enrollments e JOIN syllabi s ON e.syllabus_id=s.id WHERE s.teacher_id=$tid")->fetch_assoc()['c'];
$myTopics = $conn->query("SELECT COUNT(*) c FROM syllabus_topics st JOIN syllabi s ON st.syllabus_id=s.id WHERE s.teacher_id=$tid")->fetch_assoc()['c'];
$myMaterials = $conn->query("SELECT COUNT(*) c FROM learning_materials WHERE teacher_id=$tid")->fetch_assoc()['c'];

require_once '../includes/notifications.php';
$tNotifs = getTeacherNotifications($tid);

// Query for detailed syllabus mapping status across all assigned courses
$mappingSyllabi = $conn->query("SELECT s.*, c.course_name, c.course_code, c.units, c.prerequisite,
    (SELECT COUNT(*) FROM syllabus_topics st WHERE st.syllabus_id=s.id) as topic_count,
    (SELECT COUNT(DISTINCT ilo_code) FROM syllabus_topics st WHERE st.syllabus_id=s.id AND ilo_code IS NOT NULL AND ilo_code != '') as ilo_count,
    (SELECT COUNT(*) FROM learning_materials lm WHERE lm.syllabus_id=s.id) as material_count,
    (SELECT COUNT(*) FROM assessments a WHERE a.syllabus_id=s.id) as assessment_count,
    (SELECT COUNT(*) FROM enrollments e WHERE e.syllabus_id=s.id AND e.status='enrolled') as student_count
    FROM syllabi s 
    JOIN courses c ON s.course_id=c.id 
    WHERE s.teacher_id=$tid 
    ORDER BY s.created_at DESC");
$mappingList = [];
while ($row = $mappingSyllabi->fetch_assoc()) {
    $mappingList[] = $row;
}

$announcements = $conn->query("SELECT * FROM announcements WHERE target_role IN ('all','teacher') ORDER BY created_at DESC LIMIT 5");

// 1. Completed Read Materials
$recentReadings = $conn->query("
    SELECT tp.id, tp.student_id, tp.syllabus_topic_id, tp.read_percentage, tp.last_read_at, tp.completed_at,
           u.full_name, u.email,
           st.topic_title, st.week_number,
           s.id as syllabus_id, c.course_code, c.course_name
    FROM topic_progress tp
    JOIN users u ON tp.student_id = u.id
    JOIN syllabus_topics st ON tp.syllabus_topic_id = st.id
    JOIN syllabi s ON st.syllabus_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE s.teacher_id = $tid AND (tp.status = 'completed' OR tp.read_percentage >= 90)
    ORDER BY COALESCE(tp.completed_at, tp.last_read_at) DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

// 2. Passed / Graded Assessments
$recentAssessments = $conn->query("
    SELECT sub.id, sub.student_id, sub.score, sub.feedback, sub.submitted_at, sub.graded_at, sub.status,
           u.full_name, u.email,
           a.id as assessment_id, a.title as assessment_title, a.max_score, a.type as assessment_type,
           s.id as syllabus_id, c.course_code, c.course_name
    FROM submissions sub
    JOIN users u ON sub.student_id = u.id
    JOIN assessments a ON sub.assessment_id = a.id
    JOIN syllabi s ON a.syllabus_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE s.teacher_id = $tid AND sub.status = 'graded'
    ORDER BY COALESCE(sub.graded_at, sub.submitted_at) DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

// 3. New Enrolled Students
$recentEnrollments = $conn->query("
    SELECT e.id, e.student_id, e.enrolled_at, e.status,
           u.full_name, u.email,
           s.id as syllabus_id, c.course_code, c.course_name
    FROM enrollments e
    JOIN users u ON e.student_id = u.id
    JOIN syllabi s ON e.syllabus_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE s.teacher_id = $tid AND e.status = 'enrolled'
    ORDER BY e.enrolled_at DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

// 4. Students who completed all topics in a syllabus
$recentCompletions = $conn->query("
    SELECT e.student_id, e.syllabus_id, u.full_name, u.email, c.course_code, c.course_name,
           COUNT(DISTINCT st.id) as total_topics,
           COUNT(DISTINCT CASE WHEN tp.status = 'completed' OR tp.read_percentage >= 90 THEN st.id END) as completed_topics,
           MAX(tp.last_read_at) as completed_time
    FROM enrollments e
    JOIN syllabi s ON e.syllabus_id = s.id
    JOIN courses c ON s.course_id = c.id
    JOIN users u ON e.student_id = u.id
    JOIN syllabus_topics st ON st.syllabus_id = s.id
    LEFT JOIN topic_progress tp ON tp.syllabus_topic_id = st.id AND tp.student_id = e.student_id
    WHERE s.teacher_id = $tid AND e.status = 'enrolled'
    GROUP BY e.student_id, e.syllabus_id
    HAVING total_topics > 0 AND completed_topics = total_topics
    ORDER BY completed_time DESC
    LIMIT 30
")->fetch_all(MYSQLI_ASSOC);

// Merge all activity logs
$activityLogs = [];

foreach ($recentReadings as $r) {
    $time = !empty($r['completed_at']) ? $r['completed_at'] : $r['last_read_at'];
    $activityLogs[] = [
        'type' => 'reading',
        'badge' => 'badge-green',
        'badge_text' => 'Read Material Completed',
        'icon' => 'fa-book-reader',
        'icon_color' => '#10b981',
        'bg_tint' => '#ecfdf5',
        'student_name' => $r['full_name'],
        'student_email' => $r['email'],
        'title' => htmlspecialchars($r['full_name']) . ' completed reading material',
        'description' => '<strong style="color:var(--text);font-size:13px">' . htmlspecialchars($r['full_name']) . '</strong> finished reading <strong>' . htmlspecialchars($r['topic_title']) . '</strong> (Week ' . $r['week_number'] . ') in <span class="badge badge-blue">' . htmlspecialchars($r['course_code']) . '</span> (' . (int)$r['read_percentage'] . '% completed).',
        'url' => 'students.php?syl=' . $r['syllabus_id'] . '&student_id=' . $r['student_id'] . '#student-' . $r['student_id'],
        'url_label' => 'View Student Progress',
        'timestamp' => strtotime($time),
        'time_str' => date('M d, Y g:i A', strtotime($time)),
    ];
}

foreach ($recentAssessments as $a) {
    $time = !empty($a['graded_at']) ? $a['graded_at'] : $a['submitted_at'];
    $scoreStr = number_format($a['score'], 1) . ' / ' . number_format($a['max_score'], 1);
    $pct = $a['max_score'] > 0 ? round(($a['score'] / $a['max_score']) * 100) : 0;
    $activityLogs[] = [
        'type' => 'assessment',
        'badge' => 'badge-blue',
        'badge_text' => 'Assessment Passed / Graded',
        'icon' => 'fa-award',
        'icon_color' => '#3b82f6',
        'bg_tint' => '#eff6ff',
        'student_name' => $a['full_name'],
        'student_email' => $a['email'],
        'title' => htmlspecialchars($a['full_name']) . ' achieved score in assessment',
        'description' => '<strong style="color:var(--text);font-size:13px">' . htmlspecialchars($a['full_name']) . '</strong> scored <strong>' . $scoreStr . ' (' . $pct . '%)</strong> in <strong>' . htmlspecialchars($a['assessment_title']) . '</strong> in <span class="badge badge-blue">' . htmlspecialchars($a['course_code']) . '</span>.',
        'url' => 'grade_submission.php?id=' . $a['id'],
        'url_label' => 'View Submission & Grades',
        'timestamp' => strtotime($time),
        'time_str' => date('M d, Y g:i A', strtotime($time)),
    ];
}

foreach ($recentCompletions as $c) {
    $time = !empty($c['completed_time']) ? $c['completed_time'] : date('Y-m-d H:i:s');
    $activityLogs[] = [
        'type' => 'syllabus',
        'badge' => 'badge-purple',
        'badge_text' => 'Syllabus Completed (100%)',
        'icon' => 'fa-graduation-cap',
        'icon_color' => '#8b5cf6',
        'bg_tint' => '#f5f3ff',
        'student_name' => $c['full_name'],
        'student_email' => $c['email'],
        'title' => htmlspecialchars($c['full_name']) . ' completed entire syllabus',
        'description' => '<strong style="color:var(--text);font-size:13px">' . htmlspecialchars($c['full_name']) . '</strong> completed all <strong>' . $c['completed_topics'] . ' / ' . $c['total_topics'] . ' weekly topics</strong> (100% finished) in <span class="badge badge-purple">' . htmlspecialchars($c['course_code']) . ' ' . htmlspecialchars($c['course_name']) . '</span>.',
        'url' => 'students.php?syl=' . $c['syllabus_id'] . '&student_id=' . $c['student_id'] . '#student-' . $c['student_id'],
        'url_label' => 'View Student Progress',
        'timestamp' => strtotime($time),
        'time_str' => date('M d, Y g:i A', strtotime($time)),
    ];
}

foreach ($recentEnrollments as $e) {
    $time = $e['enrolled_at'];
    $activityLogs[] = [
        'type' => 'student',
        'badge' => 'badge-orange',
        'badge_text' => 'New Student Enrolled',
        'icon' => 'fa-user-plus',
        'icon_color' => '#f59e0b',
        'bg_tint' => '#fffbeb',
        'student_name' => $e['full_name'],
        'student_email' => $e['email'],
        'title' => htmlspecialchars($e['full_name']) . ' enrolled in course',
        'description' => '<strong style="color:var(--text);font-size:13px">' . htmlspecialchars($e['full_name']) . '</strong> (' . htmlspecialchars($e['email']) . ') newly enrolled in <span class="badge badge-blue">' . htmlspecialchars($e['course_code']) . '</span> <strong>' . htmlspecialchars($e['course_name']) . '</strong>.',
        'url' => 'students.php?syl=' . $e['syllabus_id'] . '&student_id=' . $e['student_id'] . '#student-' . $e['student_id'],
        'url_label' => 'View Student Progress',
        'timestamp' => strtotime($time),
        'time_str' => date('M d, Y g:i A', strtotime($time)),
    ];
}

// Sort newest first
usort($activityLogs, function($a, $b) {
    return $b['timestamp'] <=> $a['timestamp'];
});
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">

<div style="margin-bottom:20px">
    <h2 style="font-size:22px;font-weight:800">Welcome back, <?= htmlspecialchars(preg_split('/[\s,]+/', trim($_SESSION['full_name'] ?? 'Teacher'))[0]) ?>! 👋</h2>
    <p style="color:var(--text3)">Here's an overview of your teaching activity and syllabus mapping progress at I-Tech College.</p>
</div>

<?php if ($tNotifs['ungraded_count'] > 0): ?>
<div class="card" style="margin-bottom:20px;border-left:4px solid var(--info);background:linear-gradient(to right, rgba(53,140,212,0.06), transparent)">
    <div class="card-body" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:12px">
            <span style="display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:var(--info);color:#fff;font-size:14px">
                <i class="fas fa-inbox"></i>
            </span>
            <div>
                <strong style="font-size:14px;color:var(--text)">Action Required: <?= $tNotifs['ungraded_count'] ?> Student Submissions Pending Review</strong>
                <div style="font-size:12px;color:var(--text3);margin-top:2px">Review student submissions and assign grades and qualitative feedback.</div>
            </div>
        </div>
        <a href="grades.php?assessment=all" class="btn btn-primary btn-sm">
            <i class="fas fa-star" style="margin-right:4px"></i> Open Gradebook
        </a>
    </div>
</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card green"><div class="stat-icon green"><i class="fas fa-file-alt"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $mySyllabi ?></div><div class="stat-label">My Syllabi</div></div></div>
    <div class="stat-card blue"><div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $myStudents ?></div><div class="stat-label">My Students</div></div></div>
    <div class="stat-card orange"><div class="stat-icon orange"><i class="fas fa-list-ul"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $myTopics ?></div><div class="stat-label">Topics Mapped</div></div></div>
    <div class="stat-card green"><div class="stat-icon green"><i class="fas fa-folder-open"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $myMaterials ?></div><div class="stat-label">Materials</div></div></div>
</div>

<!-- SYLLABUS MAPPING COMMAND CENTER -->
<div class="card" style="margin-bottom:24px;border:1px solid rgba(59,130,246,0.25);box-shadow:0 4px 12px rgba(0,0,0,0.03)">
    <div class="card-header" style="background:#fff;border-bottom:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <span class="card-title" style="font-size:16px;font-weight:800;display:flex;align-items:center;gap:8px">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;background:rgba(59,130,246,0.12);color:var(--primary);font-size:14px">
                    <i class="fas fa-sitemap"></i>
                </span>
                Syllabus Mapping Command Center
            </span>
            <div style="font-size:12px;color:var(--text3);margin-top:4px">
                Manage curriculum alignment: link ILOs, lesson topics, learning materials, and assessments.
            </div>
        </div>
        <div style="display:flex;gap:8px">
            <a href="topics.php" class="btn btn-primary btn-sm">
                <i class="fas fa-external-link-alt" style="margin-right:4px"></i> Open Full Mapping Hub
            </a>
        </div>
    </div>

    <!-- Visual Mapping Pipeline Banner -->
    <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);padding:12px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;color:#f8fafc;font-size:12px;font-weight:600">
        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px">
            <span style="color:#60a5fa"><i class="fas fa-bullseye"></i> 1. ILO</span>
            <i class="fas fa-chevron-right" style="color:#64748b;font-size:10px"></i>
            <span style="color:#34d399"><i class="fas fa-book-open"></i> 2. Topic</span>
            <i class="fas fa-chevron-right" style="color:#64748b;font-size:10px"></i>
            <span style="color:#38bdf8"><i class="fas fa-file-pdf"></i> 3. Material</span>
            <i class="fas fa-chevron-right" style="color:#64748b;font-size:10px"></i>
            <span style="color:#fbbf24"><i class="fas fa-laptop-code"></i> 4. Activity</span>
            <i class="fas fa-chevron-right" style="color:#64748b;font-size:10px"></i>
            <span style="color:#a78bfa"><i class="fas fa-file-alt"></i> 5. Assessment</span>
            <i class="fas fa-chevron-right" style="color:#64748b;font-size:10px"></i>
            <span style="color:#f472b6"><i class="fas fa-chart-line"></i> 6. Student Progress</span>
        </div>
        <span style="background:rgba(255,255,255,0.12);padding:3px 10px;border-radius:20px;font-size:11px;color:#cbd5e1">
            <i class="fas fa-check-circle" style="color:#34d399;margin-right:4px"></i> OBE Aligned
        </span>
    </div>

    <div class="card-body" style="padding:20px">
        <?php if (empty($mappingList)): ?>
            <div style="padding:32px;text-align:center;color:var(--text3)">
                <i class="fas fa-sitemap" style="font-size:36px;margin-bottom:12px;display:block;opacity:0.4"></i>
                No courses assigned yet. Contact your administrator to assign subjects.
            </div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:16px">
                <?php foreach ($mappingList as $ms): 
                    $targetWeeks = 16;
                    $percent = min(100, round(($ms['topic_count'] / $targetWeeks) * 100));
                    $progressColor = $percent >= 80 ? '#10b981' : ($percent >= 40 ? '#3b82f6' : '#f59e0b');
                ?>
                <div style="border:1px solid var(--border);border-radius:10px;padding:16px;background:#fafbfc;transition:all 0.2s ease;display:flex;flex-direction:column;justify-content:space-between">
                    <div>
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px">
                            <div>
                                <span class="badge badge-blue" style="font-size:11px;font-weight:700"><?= htmlspecialchars($ms['course_code']) ?></span>
                                <span style="font-size:11px;color:var(--text3);margin-left:6px"><?= $ms['units'] ?? 3 ?> Units &bull; <?= htmlspecialchars($ms['academic_year'] ?? '2024-2025') ?></span>
                                <h4 style="font-size:15px;font-weight:700;margin:6px 0 2px;color:var(--text)"><?= htmlspecialchars($ms['course_name']) ?></h4>
                                <?php if (!empty($ms['prerequisite'])): ?>
                                    <div style="font-size:11px;color:var(--text3)"><i class="fas fa-link"></i> Prereq: <?= htmlspecialchars($ms['prerequisite']) ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="badge <?= $ms['status']==='published'?'badge-green':'badge-gray' ?>" style="text-transform:capitalize">
                                <?= $ms['status'] ?>
                            </span>
                        </div>

                        <!-- Progress Gauge Bar -->
                        <div style="margin:14px 0 12px">
                            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;margin-bottom:6px">
                                <span style="font-weight:600;color:var(--text2)">Syllabus Mapping Completion</span>
                                <strong style="color:<?= $progressColor ?>"><?= $percent ?>% (<?= $ms['topic_count'] ?>/<?= $targetWeeks ?> Wks)</strong>
                            </div>
                            <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden">
                                <div style="width:<?= $percent ?>%;height:100%;background:<?= $progressColor ?>;border-radius:4px;transition:width 0.4s ease"></div>
                            </div>
                        </div>

                        <!-- Pill Metrics -->
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px">
                            <span style="font-size:11px;background:#fff;border:1px solid var(--border);padding:4px 8px;border-radius:6px;color:var(--text2)">
                                <i class="fas fa-bullseye" style="color:var(--primary);margin-right:3px"></i> <?= $ms['ilo_count'] ?> ILOs
                            </span>
                            <span style="font-size:11px;background:#fff;border:1px solid var(--border);padding:4px 8px;border-radius:6px;color:var(--text2)">
                                <i class="fas fa-list-ul" style="color:var(--success);margin-right:3px"></i> <?= $ms['topic_count'] ?> Topics
                            </span>
                            <span style="font-size:11px;background:#fff;border:1px solid var(--border);padding:4px 8px;border-radius:6px;color:var(--text2)">
                                <i class="fas fa-file-alt" style="color:var(--warning);margin-right:3px"></i> <?= $ms['assessment_count'] ?> Assessments
                            </span>
                            <span style="font-size:11px;background:#fff;border:1px solid var(--border);padding:4px 8px;border-radius:6px;color:var(--text2)">
                                <i class="fas fa-user-graduate" style="color:var(--info);margin-right:3px"></i> <?= $ms['student_count'] ?> Students
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display:flex;gap:8px;border-top:1px solid var(--border);padding-top:12px;margin-top:4px">
                        <a href="topics.php?syl_id=<?= $ms['id'] ?>" class="btn btn-primary btn-sm" style="flex:1;text-align:center;justify-content:center">
                            <i class="fas fa-sitemap" style="margin-right:4px"></i> Manage Mapping
                        </a>
                        <a href="syllabus_edit.php?id=<?= $ms['id'] ?>" class="btn btn-secondary btn-sm" title="Add Mapping Entry / Edit Syllabus">
                            <i class="fas fa-plus"></i> Add Mapping
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="dash-grid">
<div><div class="card">
    <div class="card-header"><span class="card-title">My Recent Syllabi</span><a href="syllabi.php" class="btn btn-secondary btn-sm">View All</a></div>
    <div class="card-body" style="padding:0">
    <?php if (empty($mappingList)): ?>
    <div style="padding:32px;text-align:center;color:var(--text3)">
        <i class="fas fa-file-alt" style="font-size:32px;margin-bottom:10px;display:block"></i>
        No syllabi yet. <a href="syllabi.php">Create one</a> to get started.
    </div>
    <?php else: foreach (array_slice($mappingList, 0, 5) as $s):
        $sc=['draft'=>'badge-gray','published'=>'badge-green','archived'=>'badge-orange']; ?>
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div>
            <strong style="font-size:14px"><?= htmlspecialchars($s['course_code']) ?></strong>
            <span style="font-size:13px;color:var(--text3);margin-left:8px"><?= htmlspecialchars($s['course_name']) ?></span>
            <div style="font-size:12px;color:var(--text3);margin-top:3px"><?= $s['topic_count'] ?> topics &bull; <?= $s['student_count'] ?> students</div>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
            <span class="badge <?= $sc[$s['status']] ?? 'badge-gray' ?>"><?= $s['status'] ?></span>
            <a href="topics.php?syl_id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm" title="Syllabus Mapping"><i class="fas fa-sitemap"></i></a>
            <a href="syllabus_edit.php?id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm" title="Edit Syllabus"><i class="fas fa-edit"></i></a>
        </div>
    </div>
    <?php endforeach; endif; ?>
    </div>
</div></div>
<div><div class="card">
    <div class="card-header"><span class="card-title">Announcements</span><a href="announcements.php" class="btn btn-secondary btn-sm">View All</a></div>
    <div class="card-body" style="padding:0">
    <?php if ($announcements->num_rows === 0): ?>
    <div style="padding:32px;text-align:center;color:var(--text3)">
        <i class="fas fa-bullhorn" style="font-size:32px;margin-bottom:10px;display:block"></i>
        No announcements yet.
    </div>
    <?php else: while($a=$announcements->fetch_assoc()): ?>
    <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
        <strong style="font-size:13px"><?= htmlspecialchars($a['title']) ?></strong>
        <p style="font-size:12px;color:var(--text3);margin-top:4px"><?= nl2br(htmlspecialchars(substr($a['content'],0,120))) ?>...</p>
        <small class="text-muted"><?= date('M d, Y', strtotime($a['created_at'])) ?></small>
    </div>
    <?php endwhile; endif; ?>
    </div>
</div></div>
</div>

<!-- REAL-TIME COURSE ACTIVITY & AUDIT LOGS (PLACED AT THE VERY BOTTOM) -->
<div class="card" style="margin-top:24px;border:1px solid var(--border);box-shadow:0 4px 12px rgba(0,0,0,0.03)">
    <div class="card-header" style="background:#fff;border-bottom:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <span class="card-title" style="font-size:16px;font-weight:800;display:flex;align-items:center;gap:8px">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;background:rgba(16,185,129,0.12);color:var(--success);font-size:14px">
                    <i class="fas fa-history"></i>
                </span>
                Live Course Activity & Audit Logs
            </span>
            <div style="font-size:12px;color:var(--text3);margin-top:4px">
                Real-time tracking of reading material completions, passed assessments, syllabus completions, and student enrollments.
            </div>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            <button type="button" class="btn btn-secondary btn-sm act-filter-btn active" data-filter="all" onclick="filterActivityLogs('all', this)">All (<?= count($activityLogs) ?>)</button>
            <button type="button" class="btn btn-secondary btn-sm act-filter-btn" data-filter="reading" onclick="filterActivityLogs('reading', this)"><i class="fas fa-book-reader" style="color:var(--success);margin-right:3px"></i> Readings (<?= count($recentReadings) ?>)</button>
            <button type="button" class="btn btn-secondary btn-sm act-filter-btn" data-filter="assessment" onclick="filterActivityLogs('assessment', this)"><i class="fas fa-award" style="color:var(--primary);margin-right:3px"></i> Passed (<?= count($recentAssessments) ?>)</button>
            <button type="button" class="btn btn-secondary btn-sm act-filter-btn" data-filter="syllabus" onclick="filterActivityLogs('syllabus', this)"><i class="fas fa-graduation-cap" style="color:#8b5cf6;margin-right:3px"></i> Syllabi Done (<?= count($recentCompletions) ?>)</button>
            <button type="button" class="btn btn-secondary btn-sm act-filter-btn" data-filter="student" onclick="filterActivityLogs('student', this)"><i class="fas fa-user-plus" style="color:var(--warning);margin-right:3px"></i> New Students (<?= count($recentEnrollments) ?>)</button>
        </div>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($activityLogs)): ?>
            <div style="padding:36px;text-align:center;color:var(--text3)">
                <i class="fas fa-inbox" style="font-size:36px;margin-bottom:10px;display:block;opacity:0.4"></i>
                No course activities recorded yet. When students complete reading modules, pass assessments, or enroll, they will appear here.
            </div>
        <?php else: ?>
            <div id="activityLogsList">
                <?php foreach ($activityLogs as $idx => $log): ?>
                <div class="activity-log-row" data-type="<?= $log['type'] ?>" data-index="<?= $idx ?>" style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:16px;transition:background 0.15s ease">
                    <div style="display:flex;align-items:center;gap:14px;min-width:0">
                        <div style="width:40px;height:40px;border-radius:10px;background:<?= $log['bg_tint'] ?>;color:<?= $log['icon_color'] ?>;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">
                            <i class="fas <?= $log['icon'] ?>"></i>
                        </div>
                        <div style="min-width:0">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                <span class="badge <?= $log['badge'] ?>" style="font-size:10px;padding:2px 8px;font-weight:700">
                                    <?= $log['badge_text'] ?>
                                </span>
                                <span style="font-size:11px;color:var(--text3)">
                                    <i class="far fa-clock" style="margin-right:3px"></i> <?= $log['time_str'] ?>
                                </span>
                            </div>
                            <div style="font-size:13px;color:var(--text);margin-top:4px;line-height:1.4">
                                <?= $log['description'] ?>
                            </div>
                        </div>
                    </div>
                    <div style="flex-shrink:0">
                        <a href="<?= $log['url'] ?>" class="btn btn-secondary btn-sm" style="font-size:12px;display:inline-flex;align-items:center;gap:5px" title="<?= htmlspecialchars($log['url_label']) ?>">
                            <span><?= htmlspecialchars($log['url_label']) ?></span>
                            <i class="fas fa-arrow-right" style="font-size:10px"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Load More Controls (Limits initial display to 10) -->
            <div id="loadMoreLogsContainer" style="padding:14px 20px;display:flex;align-items:center;justify-content:space-between;border-top:1px solid var(--border);background:#fafbfc;flex-wrap:wrap;gap:10px">
                <span id="logCounterText" style="font-size:12px;color:var(--text3);font-weight:600">Showing 10 of <?= count($activityLogs) ?> activities</span>
                <button type="button" class="btn btn-secondary btn-sm" id="loadMoreLogsBtn" onclick="loadMoreLogs()" style="font-weight:600;display:inline-flex;align-items:center;gap:6px">
                    <span>Load More Activities</span> <i class="fas fa-chevron-down" style="font-size:10px"></i>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
var visibleLogsCount = 10;

function updateLogVisibility() {
    var activeFilterBtn = document.querySelector('.act-filter-btn.active');
    var activeTab = activeFilterBtn ? activeFilterBtn.getAttribute('data-filter') : 'all';
    var rows = document.querySelectorAll('.activity-log-row');
    
    var matchedCount = 0;
    var visibleCount = 0;
    
    rows.forEach(function(row) {
        var rowType = row.getAttribute('data-type');
        var matches = (activeTab === 'all' || rowType === activeTab);
        if (matches) {
            matchedCount++;
            if (matchedCount <= visibleLogsCount) {
                row.style.display = 'flex';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        } else {
            row.style.display = 'none';
        }
    });
    
    var counter = document.getElementById('logCounterText');
    var loadBtn = document.getElementById('loadMoreLogsBtn');
    var container = document.getElementById('loadMoreLogsContainer');
    
    if (counter) {
        counter.textContent = 'Showing ' + visibleCount + ' of ' + matchedCount + ' activities';
    }
    if (container) {
        if (matchedCount > visibleLogsCount) {
            container.style.display = 'flex';
            if (loadBtn) loadBtn.style.display = 'inline-flex';
        } else if (matchedCount > 0) {
            container.style.display = 'flex';
            if (loadBtn) loadBtn.style.display = 'none';
        } else {
            container.style.display = 'none';
        }
    }
}

function loadMoreLogs() {
    visibleLogsCount += 10;
    updateLogVisibility();
}

function filterActivityLogs(type, btn) {
    document.querySelectorAll('.act-filter-btn').forEach(function(b) {
        b.classList.remove('active');
        b.style.background = '';
        b.style.color = '';
    });
    btn.classList.add('active');
    btn.style.background = 'var(--primary, #2563eb)';
    btn.style.color = '#fff';
    
    visibleLogsCount = 10;
    updateLogVisibility();
}

document.addEventListener('DOMContentLoaded', updateLogVisibility);
updateLogVisibility();
</script>
</div></div></div>
</body></html>