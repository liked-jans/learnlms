<?php
require_once '../includes/config.php';
requireRole('student');
$pageTitle = 'My Progress';
$stid = $_SESSION['user_id'];

$courses=$conn->query("SELECT s.id as syl_id,c.course_code,c.course_name,u.full_name as teacher_name,s.semester,s.academic_year,
    (SELECT COUNT(*) FROM syllabus_topics st WHERE st.syllabus_id=s.id) total,
    (SELECT COUNT(*) FROM topic_progress tp JOIN syllabus_topics st ON tp.syllabus_topic_id=st.id WHERE tp.student_id=$stid AND st.syllabus_id=s.id AND tp.status='completed') done,
    (SELECT AVG(sub.score/a.max_score*100) FROM submissions sub JOIN assessments a ON sub.assessment_id=a.id WHERE sub.student_id=$stid AND a.syllabus_id=s.id AND sub.status='graded' AND sub.score IS NOT NULL AND a.max_score>0) avg_score
    FROM enrollments e JOIN syllabi s ON e.syllabus_id=s.id JOIN courses c ON s.course_id=c.id JOIN users u ON s.teacher_id=u.id WHERE e.student_id=$stid AND e.status='enrolled'");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header"><div class="page-header-left"><h2>My Progress</h2><p>Track your learning across all courses</p></div></div>
<div style="display:flex;flex-direction:column;gap:20px">
<?php while($c=$courses->fetch_assoc()):
    $pct = $c['total'] > 0 ? round($c['done']/$c['total']*100) : 0;
    $avgScore = $c['avg_score'] ? round($c['avg_score']) : null;
?>
<div class="card"><div class="card-body">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px">
        <div>
            <span style="font-size:11px;font-weight:700;color:var(--primary);text-transform:uppercase"><?= htmlspecialchars($c['course_code']) ?></span>
            <h3 style="font-size:17px;font-weight:700;margin:4px 0"><?= htmlspecialchars($c['course_name']) ?></h3>
            <p style="font-size:12px;color:var(--text3)"><?= htmlspecialchars($c['teacher_name']) ?> &bull; <?= $c['semester'] ?> Sem <?= $c['academic_year'] ?></p>
        </div>
        <div style="text-align:right">
            <div style="font-size:32px;font-weight:800;color:<?= $pct>=75?'var(--primary)':($pct>=50?'var(--warning)':'var(--danger)') ?>"><?= $pct ?>%</div>
            <div style="font-size:11px;color:var(--text3)">completion</div>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div style="text-align:center;padding:16px;background:var(--bg);border-radius:10px">
            <div style="font-size:28px;font-weight:800;color:var(--primary)"><?= $c['done'] ?>/<?= $c['total'] ?></div>
            <div style="font-size:12px;color:var(--text3);margin-top:4px">Topics Completed</div>
            <div class="progress-bar" style="margin-top:10px"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
        </div>
        <div style="text-align:center;padding:16px;background:var(--bg);border-radius:10px">
            <div style="font-size:28px;font-weight:800;color:<?= $avgScore ? ($avgScore>=75?'var(--primary)':($avgScore>=60?'var(--warning)':'var(--danger)')) : 'var(--text3)' ?>"><?= $avgScore ? $avgScore.'%' : 'N/A' ?></div>
            <div style="font-size:12px;color:var(--text3);margin-top:4px">Average Score</div>
        </div>
    </div>
    <div style="margin-top:16px;display:flex;gap:8px">
        <a href="syllabus.php?syl=<?= $c['syl_id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-map"></i> Syllabus Map</a>
        <a href="assessments.php?syl=<?= $c['syl_id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-pencil-alt"></i> Assessments</a>
    </div>
</div></div>
<?php endwhile; ?>
</div>
</div></div></div>
</body></html>