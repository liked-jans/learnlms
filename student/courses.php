<?php
require_once '../includes/config.php';
requireRole('student');
$pageTitle = 'My Courses';
$stid = $_SESSION['user_id'];
$enrolled = $conn->query("SELECT e.*,s.id as syl_id,s.semester,s.academic_year,s.status as syl_status,c.course_name,c.course_code,c.units,c.description,u.full_name as teacher_name,
    (SELECT COUNT(*) FROM syllabus_topics st WHERE st.syllabus_id=e.syllabus_id) total_topics,
    (SELECT COUNT(*) FROM topic_progress tp WHERE tp.student_id=e.student_id AND tp.syllabus_topic_id IN (SELECT id FROM syllabus_topics WHERE syllabus_id=e.syllabus_id) AND tp.status='completed') done_topics
    FROM enrollments e JOIN syllabi s ON e.syllabus_id=s.id JOIN courses c ON s.course_id=c.id JOIN users u ON s.teacher_id=u.id WHERE e.student_id=$stid ORDER BY c.course_name");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header"><div class="page-header-left"><h2>My Courses</h2><p>All your enrolled courses</p></div></div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px">
<?php while($c=$enrolled->fetch_assoc()):
    $pct = $c['total_topics'] > 0 ? round($c['done_topics']/$c['total_topics']*100) : 0; ?>
<div class="card"><div class="card-body">
    <div style="display:flex;justify-content:space-between;margin-bottom:12px">
        <div>
            <span style="font-size:11px;font-weight:700;color:var(--primary);text-transform:uppercase"><?= htmlspecialchars($c['course_code']) ?></span><br>
            <strong style="font-size:16px"><?= htmlspecialchars($c['course_name']) ?></strong>
        </div>
        <span class="badge badge-blue"><?= $c['units'] ?> units</span>
    </div>
    <p style="font-size:13px;color:var(--text3);margin-bottom:12px"><?= htmlspecialchars($c['description'] ?? 'No description') ?></p>
    <div style="font-size:12px;color:var(--text3);margin-bottom:12px">
        <i class="fas fa-user-tie"></i> <?= htmlspecialchars($c['teacher_name']) ?> &bull;
        <?= $c['semester'] ?> Sem, <?= $c['academic_year'] ?>
    </div>
    <div style="margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
            <span style="font-size:12px">Progress</span><span style="font-size:12px;font-weight:700;color:var(--primary)"><?= $pct ?>%</span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="syllabus.php?syl=<?= $c['syl_id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-map"></i> Syllabus</a>
        <a href="materials.php?syl=<?= $c['syl_id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-folder"></i> Materials</a>
        <a href="assessments.php?syl=<?= $c['syl_id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-pencil-alt"></i> Assessments</a>
    </div>
</div></div>
<?php endwhile; ?>
</div>
</div></div></div>
</body></html>
