<?php
require_once '../includes/config.php';
requireRole('teacher');
$pageTitle = 'Teacher Dashboard';
$tid = $_SESSION['user_id'];

$mySyllabi = $conn->query("SELECT COUNT(*) c FROM syllabi WHERE teacher_id=$tid")->fetch_assoc()['c'];
$myStudents = $conn->query("SELECT COUNT(DISTINCT e.student_id) c FROM enrollments e JOIN syllabi s ON e.syllabus_id=s.id WHERE s.teacher_id=$tid")->fetch_assoc()['c'];
$myTopics = $conn->query("SELECT COUNT(*) c FROM syllabus_topics st JOIN syllabi s ON st.syllabus_id=s.id WHERE s.teacher_id=$tid")->fetch_assoc()['c'];
$myMaterials = $conn->query("SELECT COUNT(*) c FROM learning_materials WHERE teacher_id=$tid")->fetch_assoc()['c'];

$syllabi = $conn->query("SELECT s.*, c.course_name,c.course_code,
    (SELECT COUNT(*) FROM syllabus_topics st WHERE st.syllabus_id=s.id) topics,
    (SELECT COUNT(*) FROM enrollments e WHERE e.syllabus_id=s.id) students
    FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.teacher_id=$tid ORDER BY s.created_at DESC LIMIT 5");
$announcements = $conn->query("SELECT * FROM announcements WHERE target_role IN ('all','teacher') ORDER BY created_at DESC LIMIT 5");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">

<div style="margin-bottom:20px">
    <h2 style="font-size:22px;font-weight:800">Welcome back, <?= htmlspecialchars(explode(' ',$_SESSION['full_name'])[0]) ?>! 👋</h2>
    <p style="color:var(--text3)">Here's an overview of your teaching activity.</p>
</div>

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

<div class="dash-grid">
<div><div class="card">
    <div class="card-header"><span class="card-title">My Recent Syllabi</span><a href="syllabi.php" class="btn btn-secondary btn-sm">View All</a></div>
    <div class="card-body" style="padding:0">
    <?php if ($syllabi->num_rows === 0): ?>
    <div style="padding:32px;text-align:center;color:var(--text3)">
        <i class="fas fa-file-alt" style="font-size:32px;margin-bottom:10px;display:block"></i>
        No syllabi yet. <a href="syllabi.php">Create one</a> to get started.
    </div>
    <?php else: while($s=$syllabi->fetch_assoc()):
        $sc=['draft'=>'badge-gray','published'=>'badge-green','archived'=>'badge-orange']; ?>
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div>
            <strong style="font-size:14px"><?= htmlspecialchars($s['course_code']) ?></strong>
            <span style="font-size:13px;color:var(--text3);margin-left:8px"><?= htmlspecialchars($s['course_name']) ?></span>
            <div style="font-size:12px;color:var(--text3);margin-top:3px"><?= $s['topics'] ?> topics &bull; <?= $s['students'] ?> students</div>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
            <span class="badge <?= $sc[$s['status']] ?>"><?= $s['status'] ?></span>
            <a href="syllabus_edit.php?id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i></a>
        </div>
    </div>
    <?php endwhile; endif; ?>
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
</div></div></div>
</body></html>
