<?php
require_once '../includes/config.php';
requireRole('teacher');
$pageTitle = 'My Students';
$tid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'enroll') {
        $studentId=(int)$_POST['student_id']; $sylId=(int)$_POST['syllabus_id'];
        $stmt=$conn->prepare("INSERT IGNORE INTO enrollments (student_id,syllabus_id) VALUES (?,?)");
        $stmt->bind_param('ii',$studentId,$sylId); $stmt->execute();
        setFlash('success','Student enrolled.');
    } elseif ($action === 'unenroll') {
        $id=(int)$_POST['id'];
        $conn->query("DELETE FROM enrollments WHERE id=$id");
        setFlash('success','Student removed.');
    }
    redirect(BASE_URL.'teacher/students.php');
}

$sylFilter = (int)($_GET['syl'] ?? 0);
$mySyllabi = $conn->query("SELECT s.*,c.course_code,c.course_name FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.teacher_id=$tid ORDER BY c.course_name");
$sylArr=[]; while($s=$mySyllabi->fetch_assoc()) $sylArr[]=$s;

if ($sylFilter) {
    $enrollments = $conn->query("SELECT e.*,u.full_name,u.email,u.username,
        (SELECT COUNT(*) FROM topic_progress tp JOIN syllabus_topics st ON tp.syllabus_topic_id=st.id WHERE tp.student_id=e.student_id AND st.syllabus_id=e.syllabus_id AND tp.status='completed') done,
        (SELECT COUNT(*) FROM syllabus_topics st WHERE st.syllabus_id=e.syllabus_id) total
        FROM enrollments e JOIN users u ON e.student_id=u.id WHERE e.syllabus_id=$sylFilter ORDER BY u.full_name");
}
$allStudents = $conn->query("SELECT * FROM users WHERE role='student' AND status='active' ORDER BY full_name");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header">
    <div class="page-header-left"><h2>My Students</h2><p>Manage student enrollments</p></div>
    <?php if ($sylFilter): ?>
    <button class="btn btn-primary" onclick="openModal('enrollModal')"><i class="fas fa-user-plus"></i> Enroll Student</button>
    <?php endif; ?>
</div>

<div class="card" style="margin-bottom:20px"><div class="card-body" style="padding:14px 20px">
<form method="GET" style="display:flex;gap:12px">
    <select name="syl" class="form-control" style="width:350px" onchange="this.form.submit()">
        <option value="">-- Select a Syllabus --</option>
        <?php foreach($sylArr as $s): ?><option value="<?= $s['id'] ?>" <?= $sylFilter==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['course_code'].' - '.$s['course_name']) ?></option><?php endforeach; ?>
    </select>
</form>
</div></div>
<?php if ($sylFilter && isset($enrollments)): ?>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Student</th><th>Email</th><th>Progress</th><th>Enrolled</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php while($e=$enrollments->fetch_assoc()):
    $pct = $e['total'] > 0 ? round($e['done']/$e['total']*100) : 0; ?>
<tr>
    <td><div style="display:flex;align-items:center;gap:8px"><div class="avatar-sm"><?= strtoupper(substr($e['full_name'],0,2)) ?></div><?= htmlspecialchars($e['full_name']) ?></div></td>
    <td><?= htmlspecialchars($e['email']) ?></td>
    <td style="min-width:160px">
        <div style="display:flex;align-items:center;gap:8px">
            <div class="progress-bar" style="flex:1"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
            <span style="font-size:12px;font-weight:600;color:var(--primary)"><?= $pct ?>%</span>
        </div>
        <small class="text-muted"><?= $e['done'] ?>/<?= $e['total'] ?> topics</small>
    </td>
    <td><?= date('M d, Y', strtotime($e['enrolled_at'])) ?></td>
    <td><span class="badge <?= $e['status']==='enrolled'?'badge-green':'badge-gray' ?>"><?= $e['status'] ?></span></td>
    <td><form method="POST" onsubmit="return confirm('Remove student?')"><input type="hidden" name="action" value="unenroll"><input type="hidden" name="id" value="<?= $e['id'] ?>"><button class="btn btn-danger btn-sm"><i class="fas fa-user-minus"></i></button></form></td>
</tr>
<?php endwhile; ?>
</tbody>
</table></div></div>

<!-- Enroll Modal -->
<div class="modal-overlay" id="enrollModal">
<div class="modal">
<div class="modal-header"><span class="modal-title">Enroll Student</span><button class="modal-close" onclick="closeModal('enrollModal')">&times;</button></div>
<form method="POST"><input type="hidden" name="action" value="enroll"><input type="hidden" name="syllabus_id" value="<?= $sylFilter ?>">
<div class="modal-body">
    <div class="form-group"><label>Select Student</label>
    <select name="student_id" class="form-control" required>
        <option value="">Choose student...</option>
        <?php $allStudents->data_seek(0); while($s=$allStudents->fetch_assoc()): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= $s['username'] ?>)</option>
        <?php endwhile; ?>
    </select></div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('enrollModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll</button>
</div>
</form></div></div>
<?php else: ?>
<div class="empty-state card"><div class="card-body">
    <i class="fas fa-users"></i><h3>Select a Syllabus</h3><p>Choose a syllabus above to view and manage students</p>
</div></div>
<?php endif; ?>

</div></div></div>
<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>
