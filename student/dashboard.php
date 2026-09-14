<?php
require_once '../includes/config.php';
requireRole('student');
$pageTitle = 'Student Dashboard';
$stid = $_SESSION['user_id'];

$enrollCount = $conn->query("SELECT COUNT(*) c FROM enrollments WHERE student_id=$stid AND status='enrolled'")->fetch_assoc()['c'];
$completedTopics = $conn->query("SELECT COUNT(*) c FROM topic_progress WHERE student_id=$stid AND status='completed'")->fetch_assoc()['c'];
$pendingAssessments = $conn->query("SELECT COUNT(*) c FROM assessments a JOIN enrollments e ON e.syllabus_id=a.syllabus_id WHERE e.student_id=$stid AND a.id NOT IN (SELECT assessment_id FROM submissions WHERE student_id=$stid)")->fetch_assoc()['c'];

$enrolledCourses = $conn->query("SELECT e.*,s.id as syllabus_id,s.academic_year,s.semester,c.course_name,c.course_code,u.full_name as teacher_name,
    (SELECT COUNT(*) FROM syllabus_topics st WHERE st.syllabus_id=e.syllabus_id) total_topics,
    (SELECT COUNT(*) FROM topic_progress tp WHERE tp.student_id=e.student_id AND tp.syllabus_topic_id IN (SELECT id FROM syllabus_topics WHERE syllabus_id=e.syllabus_id) AND tp.status='completed') done_topics
    FROM enrollments e JOIN syllabi s ON e.syllabus_id=s.id JOIN courses c ON s.course_id=c.id JOIN users u ON s.teacher_id=u.id WHERE e.student_id=$stid AND e.status='enrolled' ORDER BY c.course_name");

$announcements = $conn->query("SELECT * FROM announcements WHERE target_role IN ('all','student') ORDER BY created_at DESC LIMIT 4");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">

<div style="margin-bottom:20px">
    <h2 style="font-size:22px;font-weight:800">Hello, <?= htmlspecialchars(explode(' ',$_SESSION['full_name'])[0]) ?>! 🎓</h2>
    <p style="color:var(--text3)">Continue your learning journey</p>
</div>
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="stat-card green"><div class="stat-icon green"><i class="fas fa-book-open"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $enrollCount ?></div><div class="stat-label">Enrolled Courses</div></div></div>
    <div class="stat-card blue"><div class="stat-icon blue"><i class="fas fa-check-double"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $completedTopics ?></div><div class="stat-label">Topics Completed</div></div></div>
    <div class="stat-card orange"><div class="stat-icon orange"><i class="fas fa-clock"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $pendingAssessments ?></div><div class="stat-label">Pending Assessments</div></div></div>
</div>

<div class="dash-grid">
<div>
<h3 style="font-size:16px;font-weight:700;margin-bottom:16px">My Courses</h3>
<div style="display:flex;flex-direction:column;gap:16px">
<?php if ($enrolledCourses->num_rows === 0): ?>
<div class="card"><div class="card-body" style="text-align:center;padding:32px;color:var(--text3)">
    <i class="fas fa-book-open" style="font-size:32px;margin-bottom:10px;display:block"></i>
    You're not enrolled in any courses yet.
</div></div>
<?php else: while($c=$enrolledCourses->fetch_assoc()):
    $pct = $c['total_topics'] > 0 ? round($c['done_topics']/$c['total_topics']*100) : 0; ?>
<div class="card"><div class="card-body">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
        <div>
            <span style="font-size:11px;font-weight:700;color:var(--primary);text-transform:uppercase"><?= htmlspecialchars($c['course_code']) ?></span>
            <h4 style="font-size:15px;font-weight:700;margin:2px 0"><?= htmlspecialchars($c['course_name']) ?></h4>
            <p style="font-size:12px;color:var(--text3)">Teacher: <?= htmlspecialchars($c['teacher_name']) ?></p>
        </div>
        <span class="badge badge-green"><?= $c['semester'] ?> Sem</span>
    </div>
    <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:12px;color:var(--text3)">Progress</span><span style="font-size:12px;font-weight:700;color:var(--primary)"><?= $pct ?>%</span></div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
        <small class="text-muted"><?= $c['done_topics'] ?>/<?= $c['total_topics'] ?> topics</small>
    </div>
    <div style="display:flex;gap:8px">
        <a href="syllabus.php?syl=<?= $c['syllabus_id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-map"></i> Syllabus Map</a>
        <a href="materials.php?syl=<?= $c['syllabus_id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-folder"></i> Materials</a>
    </div>
</div></div>
<?php endwhile; endif; ?>
</div>
</div>
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
    <p style="font-size:12px;color:var(--text3);margin-top:4px"><?= htmlspecialchars(substr($a['content'],0,100)) ?>...</p>
    <small class="text-muted"><?= date('M d, Y', strtotime($a['created_at'])) ?></small>
</div>
<?php endwhile; endif; ?>
</div>
</div></div>
</div>
</div></div></div>
</body></html>
