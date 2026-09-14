<?php
require_once '../includes/config.php';
requireRole('student');
$pageTitle = 'Announcements';
$stid = $_SESSION['user_id'];

$anns = $conn->prepare("
    SELECT a.*, u.full_name as author_name, c.course_code, c.course_name
    FROM announcements a
    JOIN users u ON a.author_id = u.id
    LEFT JOIN syllabi s ON a.syllabus_id = s.id
    LEFT JOIN courses c ON s.course_id = c.id
    WHERE a.target_role IN ('all','student')
      AND (a.syllabus_id IS NULL OR a.syllabus_id IN (
          SELECT syllabus_id FROM enrollments WHERE student_id=? AND status='enrolled'
      ))
    ORDER BY a.created_at DESC
");
$anns->bind_param('i', $stid);
$anns->execute();
$anns = $anns->get_result();
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header">
    <div class="page-header-left"><h2>Announcements</h2><p>Updates from your teachers and the school</p></div>
</div>
<div class="card"><div class="card-body" style="padding:0">
<?php if ($anns->num_rows === 0): ?>
<div style="padding:40px;text-align:center;color:var(--text3)">
    <div style="font-size:48px;margin-bottom:12px">📢</div>
    <p>No announcements yet.</p>
</div>
<?php else: while($a=$anns->fetch_assoc()): ?>
<div style="padding:20px 24px;border-bottom:1px solid var(--border)">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap">
        <strong style="font-size:16px"><?= htmlspecialchars($a['title']) ?></strong>
        <?php if ($a['course_code']): ?><span class="badge badge-secondary"><?= htmlspecialchars($a['course_code']) ?></span><?php endif; ?>
    </div>
    <p style="color:var(--text3);font-size:14px;line-height:1.6"><?= nl2br(htmlspecialchars($a['content'])) ?></p>
    <p style="font-size:12px;color:var(--text3);margin-top:8px"><i class="fas fa-user"></i> <?= htmlspecialchars($a['author_name']) ?> &bull; <?= date('M d, Y g:i A', strtotime($a['created_at'])) ?></p>
</div>
<?php endwhile; endif; ?>
</div></div>
</div></div></div>
</body></html>
