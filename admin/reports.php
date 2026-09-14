<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Reports & Analytics';

$totalUsers = $conn->query("SELECT role, COUNT(*) c FROM users WHERE role!='admin' GROUP BY role")->fetch_all(MYSQLI_ASSOC);
$syllabiByStatus = $conn->query("SELECT status, COUNT(*) c FROM syllabi GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$topicsByMode = $conn->query("SELECT delivery_mode, COUNT(*) c FROM syllabus_topics GROUP BY delivery_mode ORDER BY c DESC")->fetch_all(MYSQLI_ASSOC);
$enrollStats = $conn->query("SELECT COUNT(*) c FROM enrollments WHERE status='enrolled'")->fetch_assoc()['c'];
$completedTopics = $conn->query("SELECT COUNT(*) c FROM topic_progress WHERE status='completed'")->fetch_assoc()['c'];
$totalTopics = $conn->query("SELECT COUNT(*) c FROM syllabus_topics")->fetch_assoc()['c'];
$topCourses = $conn->query("SELECT c.course_name, c.course_code, COUNT(e.id) enroll FROM courses c LEFT JOIN syllabi s ON s.course_id=c.id LEFT JOIN enrollments e ON e.syllabus_id=s.id GROUP BY c.id ORDER BY enroll DESC LIMIT 5");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header"><div class="page-header-left"><h2>Reports & Analytics</h2><p>System-wide statistics and insights</p></div></div>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
    <?php foreach($totalUsers as $u): ?>
    <div class="stat-card <?= $u['role']==='teacher'?'blue':'orange' ?>">
        <div class="stat-icon <?= $u['role']==='teacher'?'blue':'orange' ?>"><i class="fas fa-<?= $u['role']==='teacher'?'chalkboard-teacher':'user-graduate' ?>"></i></div>
        <div class="stat-info"><div class="stat-num"><?= $u['c'] ?></div><div class="stat-label"><?= ucfirst($u['role']) ?>s</div></div>
    </div>
    <?php endforeach; ?>
    <div class="stat-card green"><div class="stat-icon green"><i class="fas fa-users"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $enrollStats ?></div><div class="stat-label">Active Enrollments</div></div></div>
    <div class="stat-card blue"><div class="stat-icon blue"><i class="fas fa-check-circle"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $completedTopics ?></div><div class="stat-label">Topics Completed</div></div></div>
</div>

<div class="dash-grid">
<div>
<div class="card">
    <div class="card-header"><span class="card-title">Syllabi by Status</span></div>
    <div class="card-body">
    <?php foreach($syllabiByStatus as $s): 
        $colors=['draft'=>'#94a3b8','published'=>'var(--primary)','archived'=>'var(--accent)'];
        $total = array_sum(array_column($syllabiByStatus,'c'));
        $pct = $total ? round($s['c']/$total*100) : 0;
    ?>
    <div style="margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
            <span style="font-size:13px;font-weight:600;text-transform:capitalize"><?= $s['status'] ?></span>
            <span style="font-size:13px;color:var(--text3)"><?= $s['c'] ?> (<?= $pct ?>%)</span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $colors[$s['status']] ?>"></div></div>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<div class="card" style="margin-top:20px">
    <div class="card-header"><span class="card-title">Topics by Delivery Mode</span></div>
    <div class="card-body">
    <?php $total2 = array_sum(array_column($topicsByMode,'c'));
    $mc=['face-to-face'=>'var(--accent2)','online'=>'var(--info)','blended'=>'var(--primary)','asynchronous'=>'var(--warning)','synchronous'=>'#7c3aed'];
    foreach($topicsByMode as $m):
        $pct2 = $total2 ? round($m['c']/$total2*100) : 0;
        $col = $mc[$m['delivery_mode']] ?? 'var(--primary)'; ?>
    <div style="margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;margin-bottom:5px">
            <span style="font-size:13px;font-weight:600;text-transform:capitalize"><?= $m['delivery_mode'] ?></span>
            <span style="font-size:13px;color:var(--text3)"><?= $m['c'] ?> topics</span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct2 ?>%;background:<?= $col ?>"></div></div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
</div>

<div>
<div class="card">
    <div class="card-header"><span class="card-title">Top Courses by Enrollment</span></div>
    <div class="card-body" style="padding:0">
    <?php while($c=$topCourses->fetch_assoc()): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border)">
        <div>
            <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($c['course_code']) ?></div>
            <div style="font-size:12px;color:var(--text3)"><?= htmlspecialchars($c['course_name']) ?></div>
        </div>
        <span class="badge badge-green"><?= $c['enroll'] ?> enrolled</span>
    </div>
    <?php endwhile; ?>
    </div>
</div>

<?php 
$overallProgress = $totalTopics > 0 ? round($completedTopics/$totalTopics*100) : 0;
?>
<div class="card" style="margin-top:20px">
    <div class="card-header"><span class="card-title">Overall Topic Completion</span></div>
    <div class="card-body" style="text-align:center">
        <div style="font-size:52px;font-weight:800;color:var(--primary);line-height:1"><?= $overallProgress ?>%</div>
        <p style="color:var(--text3);margin:8px 0 20px">of all topics completed by students</p>
        <div class="progress-bar" style="height:12px"><div class="progress-fill" style="width:<?= $overallProgress ?>%"></div></div>
        <p style="font-size:12px;color:var(--text3);margin-top:8px"><?= $completedTopics ?> / <?= $totalTopics ?> topics</p>
    </div>
</div>
</div>
</div>
</div></div></div>
</body></html>
