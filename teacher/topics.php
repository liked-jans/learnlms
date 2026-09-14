<?php
require_once '../includes/config.php';
requireRole('teacher');
$pageTitle = 'Topic Mapping';
$tid = $_SESSION['user_id'];

$syllabi = $conn->query("SELECT s.*,c.course_name,c.course_code,
    (SELECT COUNT(*) FROM syllabus_topics st WHERE st.syllabus_id=s.id) topic_count,
    (SELECT COUNT(*) FROM syllabus_topics st WHERE st.syllabus_id=s.id AND st.delivery_mode='online') online_count,
    (SELECT COUNT(*) FROM syllabus_topics st WHERE st.syllabus_id=s.id AND st.delivery_mode='face-to-face') face_count,
    (SELECT COUNT(*) FROM syllabus_topics st WHERE st.syllabus_id=s.id AND st.delivery_mode='blended') blend_count
    FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.teacher_id=$tid ORDER BY s.created_at DESC");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header">
  <div class="page-header-left"><h2>Topic Mapping</h2><p>Overview of your syllabus topic delivery modes</p></div>
  <a href="syllabi.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create Syllabus</a>
</div>

<div style="display:flex;flex-direction:column;gap:20px">
<?php while($s=$syllabi->fetch_assoc()):
  $total = $s['topic_count'];
  $onlinePct = $total ? round($s['online_count']/$total*100) : 0;
  $facePct   = $total ? round($s['face_count']/$total*100) : 0;
  $blendPct  = $total ? round($s['blend_count']/$total*100) : 0;
  $otherPct  = 100 - $onlinePct - $facePct - $blendPct;
  $sc=['draft'=>'badge-gray','published'=>'badge-green','archived'=>'badge-orange'];
?>
<div class="card"><div class="card-body">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px">
    <div>
      <span style="font-size:11px;font-weight:700;color:var(--primary);text-transform:uppercase"><?= htmlspecialchars($s['course_code']) ?></span>
      <h3 style="font-size:16px;font-weight:700;margin:4px 0"><?= htmlspecialchars($s['course_name']) ?></h3>
      <p style="font-size:12px;color:var(--text3)"><?= $s['academic_year'] ?> — <?= $s['semester'] ?> Semester &bull; <?= $total ?> topics</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
      <span class="badge <?= $sc[$s['status']] ?>"><?= $s['status'] ?></span>
      <a href="syllabus_edit.php?id=<?= $s['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit Map</a>
    </div>
  </div>

  <?php if($total > 0): ?>
  <div style="margin-bottom:12px">
    <div style="display:flex;height:18px;border-radius:9px;overflow:hidden;gap:2px">
      <?php if($facePct): ?><div style="flex:<?= $facePct ?>;background:var(--accent2)" title="Face-to-Face: <?= $s['face_count'] ?>"></div><?php endif; ?>
      <?php if($onlinePct): ?><div style="flex:<?= $onlinePct ?>;background:var(--info)" title="Online: <?= $s['online_count'] ?>"></div><?php endif; ?>
      <?php if($blendPct): ?><div style="flex:<?= $blendPct ?>;background:var(--primary)" title="Blended: <?= $s['blend_count'] ?>"></div><?php endif; ?>
      <?php if($otherPct > 0): ?><div style="flex:<?= $otherPct ?>;background:var(--warning)" title="Other: rest"></div><?php endif; ?>
    </div>
  </div>
  <div style="display:flex;gap:16px;flex-wrap:wrap">
    <span class="mode-pill mode-face"><i class="fas fa-users"></i> Face-to-Face: <?= $s['face_count'] ?></span>
    <span class="mode-pill mode-online"><i class="fas fa-laptop"></i> Online: <?= $s['online_count'] ?></span>
    <span class="mode-pill mode-blended"><i class="fas fa-layer-group"></i> Blended: <?= $s['blend_count'] ?></span>
    <span class="mode-pill mode-async"><i class="fas fa-ellipsis-h"></i> Other: <?= $total - $s['face_count'] - $s['online_count'] - $s['blend_count'] ?></span>
  </div>
  <?php else: ?>
  <div style="text-align:center;padding:20px;color:var(--text3)"><i class="fas fa-map" style="font-size:24px;margin-bottom:8px;display:block"></i>No topics mapped yet. <a href="syllabus_edit.php?id=<?= $s['id'] ?>">Start mapping →</a></div>
  <?php endif; ?>
</div></div>
<?php endwhile; ?>
</div>
</div></div></div>
</body></html>
