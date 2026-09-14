<?php
require_once '../includes/config.php';
requireRole('student');
$pageTitle = 'My Assessments';
$stid = $_SESSION['user_id'];
ensureColumnExists('assessments', 'attachment_path', "varchar(255) DEFAULT NULL AFTER delivery_mode");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guard: if the uploaded file exceeded post_max_size, PHP wipes $_POST/$_FILES entirely.
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        setFlash('error', 'The file you tried to submit is too large for this server to accept. Please use a smaller file (try under 8MB) or paste your answer as text instead.');
        redirect(BASE_URL.'student/assessments.php');
    }

    $assId = (int)($_POST['assessment_id'] ?? 0);
    $text  = sanitize($_POST['text_answer'] ?? '');

    if (!$assId) {
        setFlash('error', 'Something went wrong identifying the assessment. Please try again.');
        redirect(BASE_URL.'student/assessments.php');
    }

    $filePath = null;
    if (!empty($_FILES['submission_file']['name'])) {
        $fileErr = $_FILES['submission_file']['error'];

        if ($fileErr !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'Your file is larger than this server allows (upload_max_filesize). Please choose a smaller file.',
                UPLOAD_ERR_FORM_SIZE  => 'Your file is larger than the form allows. Please choose a smaller file.',
                UPLOAD_ERR_PARTIAL    => 'Your file was only partially uploaded. Please try again.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary folder for uploads. Please contact your teacher/admin.',
                UPLOAD_ERR_CANT_WRITE => 'Server failed to write your file to disk. Please contact your teacher/admin.',
                UPLOAD_ERR_EXTENSION  => 'A server extension blocked the file upload.',
            ];
            setFlash('error', ($uploadErrors[$fileErr] ?? 'Your file failed to upload (error code '.$fileErr.').') . ' Nothing was submitted yet — please try again, or submit a text answer instead.');
            redirect(BASE_URL.'student/assessments.php');
        }

        $destDir = '../uploads/materials/';
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        $ext = pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION);
        $fname = uniqid('sub_') . '.' . $ext;

        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $destDir . $fname)) {
            $filePath = $fname;
        } else {
            setFlash('error', 'Your file could not be saved on the server. Please try again.');
            redirect(BASE_URL.'student/assessments.php');
        }
    }

    $stmt = $conn->prepare("INSERT INTO submissions (assessment_id,student_id,text_answer,file_path) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE text_answer=VALUES(text_answer),file_path=VALUES(file_path),submitted_at=NOW()");
    $stmt->bind_param('iiss', $assId, $stid, $text, $filePath);

    try {
        if ($stmt->execute()) {
            setFlash('success', 'Submitted successfully!');
        } else {
            setFlash('error', 'Could not save your submission: ' . $stmt->error);
        }
    } catch (\mysqli_sql_exception $e) {
        setFlash('error', 'Could not save your submission: ' . $e->getMessage());
    }

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
<thead><tr><th>Assessment</th><th>Course</th><th>Type</th><th>Due</th><th>Materials</th><th>Score</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php while($a=$assessments->fetch_assoc()):
    $isOverdue = $a['due_date'] && strtotime($a['due_date']) < time() && !$a['sub_id'];
?>
<tr>
    <td><strong><?= htmlspecialchars($a['title']) ?></strong><?php if($a['description']): ?><br><small class="text-muted"><?= htmlspecialchars(substr($a['description'],0,60)) ?></small><?php endif; ?></td>
    <td><?= htmlspecialchars($a['course_code']) ?></td>
    <td><span class="badge <?= $tc[$a['type']]??'badge-gray' ?>"><?= $a['type'] ?></span></td>
    <td><?php if($a['due_date']): ?><span style="<?= $isOverdue?'color:var(--danger);font-weight:600':'' ?>"><?= date('M d, Y', strtotime($a['due_date'])) ?></span><?php else: ?><span class="text-muted">None</span><?php endif; ?></td>
    <td>
        <?php if (!empty($a['attachment_path'] ?? null)): ?>
            <a href="<?= BASE_URL ?>uploads/assessments/<?= htmlspecialchars($a['attachment_path']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Download instructions/materials"><i class="fas fa-paperclip"></i> File</a>
        <?php else: ?>
            <span class="text-muted"><small>None</small></span>
        <?php endif; ?>
    </td>
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
<form method="POST" enctype="multipart/form-data" id="submitForm" onsubmit="return validateSubmitForm()"><input type="hidden" name="assessment_id" id="submitAssId">
<div class="modal-body">
    <div id="submitDesc" style="padding:12px;background:var(--bg);border-radius:8px;margin-bottom:16px"></div>
    <div class="form-group"><label>Your Answer / Response</label><textarea name="text_answer" class="form-control" rows="5" placeholder="Type your answer here..."></textarea></div>
    <div class="form-group">
        <label>Or Upload File <span style="color:var(--text3);font-weight:400">(max 8MB)</span></label>
        <input type="file" name="submission_file" id="submissionFile" class="form-control" onchange="checkSubFileSize(this)">
        <div id="subFileSizeWarning" style="color:var(--danger);font-size:12px;margin-top:6px;display:none">This file is bigger than 8MB and may fail to upload on this server. Try a smaller file.</div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('submitModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit</button>
</div>
</form></div></div>

</div></div></div>
<script>
var BASE_URL = <?= json_encode(BASE_URL) ?>;
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
function submitAssessment(a){
    document.getElementById('submitAssId').value=a.id;
    document.getElementById('submitModalTitle').textContent='Submit: '+a.title;
    let materialsLink = '';
    if (a.attachment_path) {
        materialsLink = `<br><a href="${BASE_URL}uploads/assessments/${a.attachment_path}" target="_blank"><i class="fas fa-paperclip"></i> View instructions/materials from your teacher</a>`;
    }
    document.getElementById('submitDesc').innerHTML=`<strong>${a.title}</strong><br><small style="color:var(--text3)">${a.description||''}</small><br><small>Max Score: ${a.max_score}</small>${materialsLink}`;
    openModal('submitModal');
}
function checkSubFileSize(input){
    const warn=document.getElementById('subFileSizeWarning');
    if(input.files && input.files[0] && input.files[0].size > 8*1024*1024){
        warn.style.display='block';
    } else {
        warn.style.display='none';
    }
}
function validateSubmitForm(){
    const f=document.getElementById('submissionFile');
    if(f.files && f.files[0] && f.files[0].size > 25*1024*1024){
        alert('This file is way too large (over 25MB) and will not upload. Please choose a smaller file.');
        return false;
    }
    return true;
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>
