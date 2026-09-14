<?php
require_once '../includes/config.php';
requireRole('teacher');
$pageTitle = 'Assessments';
$tid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $sylId=(int)$_POST['syllabus_id']; $topicId=$_POST['topic_id']?:(null); if($topicId)$topicId=(int)$topicId;
        $title=sanitize($_POST['title']); $desc=sanitize($_POST['description']); $type=sanitize($_POST['type']);
        $max=(float)$_POST['max_score']; $due=$_POST['due_date']?:null; $dm=sanitize($_POST['delivery_mode']);
        $stmt=$conn->prepare("INSERT INTO assessments (syllabus_id,topic_id,teacher_id,title,description,type,max_score,due_date,delivery_mode) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iiisssdss',$sylId,$topicId,$tid,$title,$desc,$type,$max,$due,$dm);
        $stmt->execute();
        setFlash('success','Assessment created.');
    } elseif ($action === 'grade') {
        $subId=(int)$_POST['submission_id']; $score=(float)$_POST['score']; $feedback=sanitize($_POST['feedback']);
        $stmt=$conn->prepare("UPDATE submissions SET score=?,feedback=?,status='graded',graded_at=NOW() WHERE id=?");
        $stmt->bind_param('dsi',$score,$feedback,$subId); $stmt->execute();
        setFlash('success','Graded.');
    } elseif ($action === 'delete') {
        $id=(int)$_POST['id'];
        $conn->query("DELETE FROM assessments WHERE id=$id AND teacher_id=$tid");
        setFlash('success','Deleted.');
    }
    redirect(BASE_URL.'teacher/assessments.php');
}

$sylFilter=(int)($_GET['syl']??0);
$mySyllabi=$conn->query("SELECT s.*,c.course_code,c.course_name FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.teacher_id=$tid");
$sylArr=[]; while($s=$mySyllabi->fetch_assoc()) $sylArr[]=$s;

$where = "a.teacher_id=$tid"; if($sylFilter) $where.=" AND a.syllabus_id=$sylFilter";
$assessments=$conn->query("SELECT a.*,c.course_code,(SELECT COUNT(*) FROM submissions sub WHERE sub.assessment_id=a.id) subs FROM assessments a LEFT JOIN syllabi s ON a.syllabus_id=s.id LEFT JOIN courses c ON s.course_id=c.id WHERE $where ORDER BY a.created_at DESC");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header">
    <div class="page-header-left"><h2>Assessments</h2></div>
    <button class="btn btn-primary" onclick="openModal('addAssModal')"><i class="fas fa-plus"></i> Create Assessment</button>
</div>

<div class="card" style="margin-bottom:20px"><div class="card-body" style="padding:14px 20px">
<form method="GET" style="display:flex;gap:12px">
<select name="syl" class="form-control" style="width:300px" onchange="this.form.submit()">
<option value="">All Syllabi</option>
<?php foreach($sylArr as $s): ?><option value="<?= $s['id'] ?>" <?= $sylFilter==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['course_code'].' - '.$s['course_name']) ?></option><?php endforeach; ?>
</select>
</form></div></div>

<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Title</th><th>Course</th><th>Type</th><th>Max Score</th><th>Due Date</th><th>Mode</th><th>Submissions</th><th>Actions</th></tr></thead>
<tbody>
<?php while($a=$assessments->fetch_assoc()): 
$tc=['quiz'=>'badge-green','assignment'=>'badge-blue','exam'=>'badge-red','project'=>'badge-orange','activity'=>'badge-purple']; ?>
<tr>
    <td><strong><?= htmlspecialchars($a['title']) ?></strong><?php if($a['description']): ?><br><small class="text-muted"><?= htmlspecialchars(substr($a['description'],0,50)) ?></small><?php endif; ?></td>
    <td><?= $a['course_code'] ? htmlspecialchars($a['course_code']) : '-' ?></td>
    <td><span class="badge <?= $tc[$a['type']] ?? 'badge-gray' ?>"><?= $a['type'] ?></span></td>
    <td><?= $a['max_score'] ?></td>
    <td><?= $a['due_date'] ? date('M d, Y g:i A', strtotime($a['due_date'])) : '<span class="text-muted">No deadline</span>' ?></td>
    <td><span class="mode-pill mode-<?= $a['delivery_mode']==='online'?'online':($a['delivery_mode']==='offline'?'face':'blended') ?>"><?= $a['delivery_mode'] ?></span></td>
    <td><a href="grades.php?assessment=<?= $a['id'] ?>" class="badge badge-blue"><?= $a['subs'] ?> submissions</a></td>
    <td><div class="action-btns">
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></form>
    </div></td>
</tr>
<?php endwhile; ?>
</tbody>
</table></div></div>

<!-- Add Assessment Modal -->
<div class="modal-overlay" id="addAssModal">
<div class="modal" style="max-width:640px">
<div class="modal-header"><span class="modal-title">Create Assessment</span><button class="modal-close" onclick="closeModal('addAssModal')">&times;</button></div>
<form method="POST"><input type="hidden" name="action" value="add">
<div class="modal-body">
    <div class="form-row">
        <div class="form-group"><label>Syllabus</label>
        <select name="syllabus_id" class="form-control" required onchange="loadTopics2(this.value)">
            <option value="">Select...</option>
            <?php foreach($sylArr as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['course_code'].' - '.$s['course_name']) ?></option><?php endforeach; ?>
        </select></div>
        <div class="form-group"><label>Link to Topic</label><select name="topic_id" id="assTopic" class="form-control"><option value="">Not linked</option></select></div>
    </div>
    <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" required></div>
    <div class="form-group"><label>Description / Instructions</label><textarea name="description" class="form-control" rows="3"></textarea></div>
    <div class="form-row">
        <div class="form-group"><label>Type</label><select name="type" class="form-control"><option>quiz</option><option>assignment</option><option>exam</option><option>project</option><option>activity</option></select></div>
        <div class="form-group"><label>Max Score</label><input type="number" name="max_score" class="form-control" value="100" min="1"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Due Date (optional)</label><input type="datetime-local" name="due_date" class="form-control"></div>
        <div class="form-group"><label>Delivery Mode</label><select name="delivery_mode" class="form-control"><option>both</option><option>online</option><option>offline</option></select></div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('addAssModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create</button>
</div>
</form></div></div>

</div></div></div>
<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
function loadTopics2(sylId){
    if(!sylId)return;
    fetch('get_topics.php?syl='+sylId).then(r=>r.json()).then(data=>{
        const sel=document.getElementById('assTopic');
        sel.innerHTML='<option value="">Not linked</option>';
        data.forEach(t=>sel.innerHTML+=`<option value="${t.id}">Week ${t.week_number}: ${t.topic_title}</option>`);
    });
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>
