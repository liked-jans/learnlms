<?php
require_once '../includes/config.php';
requireRole('student');
$pageTitle = 'My Assessments';
$stid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assId=(int)$_POST['assessment_id']; $text=sanitize($_POST['text_answer']??'');
    $filePath=null;
    if(isset($_FILES['submission_file'])&&$_FILES['submission_file']['error']===0) {
        $ext=pathinfo($_FILES['submission_file']['name'],PATHINFO_EXTENSION);
        $fname=uniqid('sub_').'.'.$ext;
        if(move_uploaded_file($_FILES['submission_file']['tmp_name'],'../uploads/materials/'.$fname)) $filePath=$fname;
    }
    $stmt=$conn->prepare("INSERT INTO submissions (assessment_id,student_id,text_answer,file_path) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE text_answer=VALUES(text_answer),file_path=VALUES(file_path),submitted_at=NOW()");
    $stmt->bind_param('iiss',$assId,$stid,$text,$filePath); $stmt->execute();
    setFlash('success','Submitted successfully!');
    redirect(BASE_URL.'student/assessments.php');
}

$sylFilter=(int)($_GET['syl']??0);
$mySyllabi=$conn->query("SELECT s.*,c.course_code,c.course_name FROM enrollments e JOIN syllabi s ON e.syllabus_id=s.id JOIN courses c ON s.course_id=c.id WHERE e.student_id=$stid AND e.status='enrolled'");
$sylArr=[]; while($s=$mySyllabi->fetch_assoc()) $sylArr[]=$s;

$where="e.student_id=$stid AND e.status='enrolled'";
if($sylFilter) $where.=" AND a.syllabus_id=$sylFilter";
$assessments=$conn->query("SELECT a.*,c.course_code,sub.id as sub_id,sub.score,sub.status as sub_status,sub.submitted_at,sub.feedback FROM assessments a JOIN enrollments e ON e.syllabus_id=a.syllabus_id JOIN courses c ON c.id=(SELECT course_id FROM syllabi WHERE id=a.syllabus_id) LEFT JOIN submissions sub ON sub.assessment_id=a.id AND sub.student_id=$stid WHERE $where GROUP BY a.id ORDER BY a.due_date IS NULL,a.due_date ASC");
$tc=['quiz'=>'badge-green','assignment'=>'badge-blue','exam'=>'badge-red','project'=>'badge-orange','activity'=>'badge-purple'];
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header"><div class="page-header-left"><h2>My Assessments</h2></div></div>
<div class="card" style="margin-bottom:20px"><div class="card-body" style="padding:14px 20px">
<form method="GET" style="display:flex;gap:12px">
<select name="syl" class="form-control" style="width:320px" onchange="this.form.submit()">
<option value="">All Courses</option>
<?php foreach($sylArr as $s): ?><option value="<?= $s['id'] ?>" <?= $sylFilter==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['course_code'].' - '.$s['course_name']) ?></option><?php endforeach; ?>
</select>
</form></div></div>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Assessment</th><th>Course</th><th>Type</th><th>Due</th><th>Score</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php while($a=$assessments->fetch_assoc()):
    $isOverdue = $a['due_date'] && strtotime($a['due_date']) < time() && !$a['sub_id'];
?>
<tr>
    <td><strong><?= htmlspecialchars($a['title']) ?></strong><?php if($a['description']): ?><br><small class="text-muted"><?= htmlspecialchars(substr($a['description'],0,60)) ?></small><?php endif; ?></td>
    <td><?= htmlspecialchars($a['course_code']) ?></td>
    <td><span class="badge <?= $tc[$a['type']]??'badge-gray' ?>"><?= $a['type'] ?></span></td>
    <td><?php if($a['due_date']): ?><span style="<?= $isOverdue?'color:var(--danger);font-weight:600':'' ?>"><?= date('M d, Y', strtotime($a['due_date'])) ?></span><?php else: ?><span class="text-muted">None</span><?php endif; ?></td>
    <td><?= $a['score']!==null ? '<strong style="color:var(--primary)">'.$a['score'].'</strong>/'.$a['max_score'] : '<span class="text-muted">-</span>' ?></td>
    <td><?php
        if($a['sub_id']) {
            $ss=['submitted'=>'badge-orange','graded'=>'badge-green','late'=>'badge-red'];
            echo '<span class="badge '.($ss[$a['sub_status']]??'badge-gray').'">'.ucfirst($a['sub_status']).'</span>';
            if($a['feedback']) echo '<br><small class="text-muted">Has feedback</small>';
        } elseif($isOverdue) { echo '<span class="badge badge-red">Overdue</span>'; }
        else { echo '<span class="badge badge-gray">Pending</span>'; }
    ?></td>
    <td>
        <?php if(!$a['sub_id'] && !$isOverdue): ?>
        <button class="btn btn-primary btn-sm" onclick='submitAssessment(<?= json_encode($a) ?>)'><i class="fas fa-paper-plane"></i> Submit</button>
        <?php elseif($a['sub_id']): ?>
        <span class="text-muted" style="font-size:12px"><i class="fas fa-check"></i> Submitted</span>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table></div></div>

<div class="modal-overlay" id="submitModal">
<div class="modal" style="max-width:580px">
<div class="modal-header"><span class="modal-title" id="submitModalTitle">Submit Assessment</span><button class="modal-close" onclick="closeModal('submitModal')">&times;</button></div>
<form method="POST" enctype="multipart/form-data"><input type="hidden" name="assessment_id" id="submitAssId">
<div class="modal-body">
    <div id="submitDesc" style="padding:12px;background:var(--bg);border-radius:8px;margin-bottom:16px"></div>
    <div class="form-group"><label>Your Answer / Response</label><textarea name="text_answer" class="form-control" rows="5" placeholder="Type your answer here..."></textarea></div>
    <div class="form-group"><label>Or Upload File</label><input type="file" name="submission_file" class="form-control"></div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('submitModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit</button>
</div>
</form></div></div>

</div></div></div>
<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
function submitAssessment(a){
    document.getElementById('submitAssId').value=a.id;
    document.getElementById('submitModalTitle').textContent='Submit: '+a.title;
    document.getElementById('submitDesc').innerHTML=`<strong>${a.title}</strong><br><small style="color:var(--text3)">${a.description||''}</small><br><small>Max Score: ${a.max_score}</small>`;
    openModal('submitModal');
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>
