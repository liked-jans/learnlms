<?php
require_once '../includes/config.php';
requireRole('teacher');
$pageTitle = 'Announcements';
$tid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $title   = sanitize($_POST['title']);
        $content = sanitize($_POST['content']);
        $target  = sanitize($_POST['target_role']);
        if (!in_array($target, ['all', 'student'], true)) $target = 'student';
        $sylId = !empty($_POST['syllabus_id']) ? (int)$_POST['syllabus_id'] : null;

        $stmt = $conn->prepare("INSERT INTO announcements (author_id,syllabus_id,title,content,target_role) VALUES (?,?,?,?,?)");
        $stmt->bind_param('iisss', $tid, $sylId, $title, $content, $target);
        $stmt->execute();
        setFlash('success', 'Announcement posted.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id=? AND author_id=?");
        $stmt->bind_param('ii', $id, $tid);
        $stmt->execute();
        setFlash('success', 'Deleted.');
    }
    redirect(BASE_URL.'teacher/announcements.php');
}

$anns = $conn->prepare("
    SELECT a.*, c.course_code, c.course_name
    FROM announcements a
    LEFT JOIN syllabi s ON a.syllabus_id = s.id
    LEFT JOIN courses c ON s.course_id = c.id
    WHERE a.author_id=?
    ORDER BY a.created_at DESC
");
$anns->bind_param('i', $tid);
$anns->execute();
$anns = $anns->get_result();

$mySyllabi = $conn->prepare("SELECT s.id, c.course_code, c.course_name, s.academic_year, s.semester FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.teacher_id=? ORDER BY c.course_code");
$mySyllabi->bind_param('i', $tid);
$mySyllabi->execute();
$mySyllabi = $mySyllabi->get_result();
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header">
    <div class="page-header-left"><h2>Announcements</h2></div>
    <button class="btn btn-primary" onclick="openModal('annModal')"><i class="fas fa-plus"></i> Post Announcement</button>
</div>
<div class="card"><div class="card-body" style="padding:0">
<?php if ($anns->num_rows === 0): ?>
<div style="padding:40px;text-align:center;color:var(--text3)">
    <div style="font-size:48px;margin-bottom:12px">📢</div>
    <p>No announcements yet. Click <strong>Post Announcement</strong> to notify your students.</p>
</div>
<?php else: while($a=$anns->fetch_assoc()):
    $tc=['all'=>'badge-blue','student'=>'badge-green']; ?>
<div style="padding:20px 24px;border-bottom:1px solid var(--border)">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px">
        <div style="flex:1">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap">
                <strong style="font-size:16px"><?= htmlspecialchars($a['title']) ?></strong>
                <span class="badge <?= $tc[$a['target_role']] ?? 'badge-blue' ?>">For: <?= htmlspecialchars($a['target_role']) ?></span>
                <?php if ($a['course_code']): ?><span class="badge badge-secondary"><?= htmlspecialchars($a['course_code']) ?></span><?php endif; ?>
            </div>
            <p style="color:var(--text3);font-size:14px;line-height:1.6"><?= nl2br(htmlspecialchars($a['content'])) ?></p>
            <p style="font-size:12px;color:var(--text3);margin-top:8px"><i class="fas fa-clock"></i> <?= date('M d, Y g:i A', strtotime($a['created_at'])) ?></p>
        </div>
        <form method="POST" onsubmit="return confirm('Delete?')">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $a['id'] ?>">
            <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
        </form>
    </div>
</div>
<?php endwhile; endif; ?>
</div></div>

<div class="modal-overlay" id="annModal">
<div class="modal">
<div class="modal-header"><span class="modal-title">Post Announcement</span><button class="modal-close" onclick="closeModal('annModal')">&times;</button></div>
<form method="POST"><input type="hidden" name="action" value="add">
<div class="modal-body">
    <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" required></div>
    <div class="form-group"><label>Content</label><textarea name="content" class="form-control" rows="5" required></textarea></div>
    <div class="form-group"><label>Class / Syllabus (optional)</label>
    <select name="syllabus_id" class="form-control">
        <option value="">-- Not tied to a specific class --</option>
        <?php $mySyllabi->data_seek(0); while($s=$mySyllabi->fetch_assoc()): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['course_code'].' - '.$s['course_name'].' ('.$s['academic_year'].' '.$s['semester'].' Sem)') ?></option>
        <?php endwhile; ?>
    </select></div>
    <div class="form-group"><label>Target Audience</label>
    <select name="target_role" class="form-control"><option value="student">Students only</option><option value="all">Everyone</option></select></div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('annModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-bullhorn"></i> Post</button>
</div>
</form></div></div>
</div></div></div>
<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>
