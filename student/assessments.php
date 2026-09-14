<?php
require_once '../includes/config.php';
requireRole('student');
$pageTitle = 'My Assessments';
$stid = $_SESSION['user_id'];
ensureColumnExists('assessments', 'attachment_path', "varchar(255) DEFAULT NULL AFTER delivery_mode");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token expired or invalid. Please try again.');
        redirect(BASE_URL.'student/assessments.php');
    }

    // Guard: if the uploaded file exceeded post_max_size, PHP wipes $_POST/$_FILES entirely.
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        setFlash('error', 'The file you tried to submit is too large for this server to accept. Please use a smaller file (under 25MB) or submit via Google Docs/Sheets link.');
        redirect(BASE_URL.'student/assessments.php');
    }

    $assId = (int)($_POST['assessment_id'] ?? 0);
    $text  = sanitize($_POST['text_answer'] ?? '');
    $googleDocUrl = trim($_POST['google_doc_url'] ?? '');

    if (!$assId) {
        setFlash('error', 'Something went wrong identifying the assessment. Please try again.');
        redirect(BASE_URL.'student/assessments.php');
    }

    // Fetch assessment to check submission type and auto-grade settings
    $stmtAss = $conn->prepare("SELECT * FROM assessments WHERE id = ?");
    $stmtAss->bind_param('i', $assId);
    $stmtAss->execute();
    $ass = $stmtAss->get_result()->fetch_assoc();
    if (!$ass) {
        setFlash('error', 'Assessment not found.');
        redirect(BASE_URL.'student/assessments.php');
    }

    $subType = $ass['submission_type'] ?? 'google_docs_sheets';
    $isGoogleSubmission = ($subType === 'google_docs_sheets' || !empty($googleDocUrl));

    if ($subType === 'google_docs_sheets' && empty($googleDocUrl)) {
        setFlash('error', 'Please provide a valid Google Docs or Google Sheets shared link.');
        redirect(BASE_URL.'student/assessments.php');
    }

    if (!empty($googleDocUrl)) {
        if (!filter_var($googleDocUrl, FILTER_VALIDATE_URL) || (!str_contains($googleDocUrl, 'docs.google.com') && !str_contains($googleDocUrl, 'drive.google.com'))) {
            setFlash('error', 'Please enter a valid Google Docs or Google Sheets link (starting with https://docs.google.com/...).');
            redirect(BASE_URL.'student/assessments.php');
        }
    }

    $filePath = null;
    if (!empty($_FILES['submission_file']['name'])) {
        $validation = validateUploadedFile($_FILES['submission_file'], ['pdf','doc','docx','ppt','pptx','jpg','jpeg','png','zip','xls','xlsx'], 26214400);
        if (!$validation['valid']) {
            setFlash('error', $validation['error'] . ' Please upload a valid document or image.');
            redirect(BASE_URL.'student/assessments.php');
        }

        $destDir = '../uploads/materials/';
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        $ext = $validation['extension'];
        $fname = uniqid('sub_') . '.' . $ext;

        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $destDir . $fname)) {
            $filePath = $fname;
        } else {
            setFlash('error', 'Your file could not be saved on the server. Please try again.');
            redirect(BASE_URL.'student/assessments.php');
        }
    }

    // Auto-grading logic:
    $score = null;
    $status = 'submitted';
    $isAutoGraded = 0;
    $feedback = null;

    if ($isGoogleSubmission) {
        $autoPercent = (float)($ass['auto_grade_percent'] ?? 100.00);
        $score = round(($ass['max_score'] * $autoPercent) / 100, 2);
        $status = 'graded';
        $isAutoGraded = 1;
        $feedback = "Automated Initial Grade: Google Docs/Sheets verified. Teacher may review and adjust score.";
    }

    $stmt = $conn->prepare("
        INSERT INTO submissions (assessment_id, student_id, text_answer, file_path, google_doc_url, score, feedback, status, is_auto_graded, submitted_at, graded_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), " . ($status === 'graded' ? 'NOW()' : 'NULL') . ")
        ON DUPLICATE KEY UPDATE
            text_answer = VALUES(text_answer),
            file_path = VALUES(file_path),
            google_doc_url = VALUES(google_doc_url),
            score = IF(is_auto_graded = 1 OR score IS NULL, VALUES(score), score),
            feedback = IF(feedback IS NULL OR feedback = '', VALUES(feedback), feedback),
            status = VALUES(status),
            is_auto_graded = VALUES(is_auto_graded),
            submitted_at = NOW(),
            graded_at = IF(VALUES(status) = 'graded', NOW(), graded_at)
    ");
    $stmt->bind_param('iisssdssi', $assId, $stid, $text, $filePath, $googleDocUrl, $score, $feedback, $status, $isAutoGraded);

    try {
        if ($stmt->execute()) {
            if ($isAutoGraded) {
                setFlash('success', 'Submitted successfully! Your work was automatically graded (' . $score . '/' . $ass['max_score'] . '). Your teacher can review your Google Doc/Sheet anytime.');
            } else {
                setFlash('success', 'Submitted successfully!');
            }
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
$assessments=$conn->query("SELECT a.*,c.course_code,sub.id as sub_id,sub.score,sub.status as sub_status,sub.submitted_at,sub.feedback,sub.google_doc_url,sub.is_auto_graded FROM assessments a JOIN enrollments e ON e.syllabus_id=a.syllabus_id JOIN courses c ON c.id=(SELECT course_id FROM syllabi WHERE id=a.syllabus_id) LEFT JOIN submissions sub ON sub.assessment_id=a.id AND sub.student_id=$stid WHERE $where GROUP BY a.id ORDER BY a.due_date IS NULL,a.due_date ASC");
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
<thead><tr><th>Assessment</th><th>Course</th><th>Type</th><th>Due</th><th>Materials / Template</th><th>Score</th><th>Status</th><th>Action</th></tr></thead>
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
        <?php if (!empty($a['google_template_url'])): ?>
            <a href="<?= htmlspecialchars($a['google_template_url']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="color:#0f9d58;margin-bottom:3px;display:inline-flex;align-items:center;gap:4px" title="Teacher Template">
                <i class="fab fa-google-drive"></i> Template ↗
            </a><br>
        <?php endif; ?>
        <?php if (!empty($a['attachment_path'] ?? null)): ?>
            <a href="<?= BASE_URL ?>uploads/assessments/<?= htmlspecialchars($a['attachment_path']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Download instructions/materials"><i class="fas fa-paperclip"></i> File</a>
        <?php endif; ?>
        <?php if (empty($a['google_template_url']) && empty($a['attachment_path'])): ?>
            <span class="text-muted"><small>None</small></span>
        <?php endif; ?>
    </td>
    <td><?= $a['score']!==null ? '<strong style="color:var(--primary)">'.$a['score'].'</strong>/'.$a['max_score'] : '<span class="text-muted">-</span>' ?></td>
    <td><?php
        if($a['sub_id']) {
            $ss=['submitted'=>'badge-orange','graded'=>'badge-green','late'=>'badge-red'];
            echo '<span class="badge '.($ss[$a['sub_status']]??'badge-gray').'">'.ucfirst($a['sub_status']).'</span>';
            if ($a['is_auto_graded']) {
                echo '<br><small style="color:#059669;font-weight:600"><i class="fas fa-robot"></i> Auto-Graded</small>';
            }
            if($a['feedback']) echo '<br><small class="text-muted" title="'.htmlspecialchars($a['feedback']).'">Has feedback</small>';
        } elseif($isOverdue) { echo '<span class="badge badge-red">Overdue</span>'; }
        else { echo '<span class="badge badge-gray">Pending</span>'; }
    ?></td>
    <td>
        <?php if(!$a['sub_id'] && !$isOverdue): ?>
        <button class="btn btn-primary btn-sm" onclick='submitAssessment(<?= json_encode($a) ?>)'><i class="fas fa-paper-plane"></i> Submit</button>
        <?php elseif($a['sub_id']): ?>
        <span class="text-muted" style="font-size:12px"><i class="fas fa-check" style="color:var(--success)"></i> Submitted</span>
        <?php if(!empty($a['google_doc_url'])): ?>
            <br><a href="<?= htmlspecialchars($a['google_doc_url']) ?>" target="_blank" style="font-size:11px;color:#0f9d58;font-weight:600;display:inline-flex;align-items:center;gap:3px;margin-top:4px"><i class="fab fa-google-drive"></i> Open Link ↗</a>
        <?php endif; ?>
        <br><button class="btn btn-secondary btn-sm" style="font-size:11px;padding:2px 8px;margin-top:4px" onclick='submitAssessment(<?= json_encode($a) ?>)'><i class="fas fa-edit"></i> Resubmit</button>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table></div></div>

<div class="modal-overlay" id="submitModal">
<div class="modal" style="max-width:620px">
<div class="modal-header"><span class="modal-title" id="submitModalTitle">Submit Assessment</span><button class="modal-close" onclick="closeModal('submitModal')">&times;</button></div>
<form method="POST" enctype="multipart/form-data" id="submitForm" onsubmit="return validateSubmitForm()">
<?= csrfField() ?>
<input type="hidden" name="assessment_id" id="submitAssId">
<div class="modal-body">
    <div id="submitDesc" style="padding:14px;background:var(--bg);border-radius:8px;margin-bottom:16px;border:1px solid var(--border)"></div>

    <!-- Google Docs / Sheets Input Container -->
    <div id="googleGroup" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;margin-bottom:16px">
        <div style="font-weight:700;font-size:13px;color:#166534;margin-bottom:8px;display:flex;align-items:center;gap:6px">
            <i class="fab fa-google-drive"></i> Google Docs / Sheets Submission
        </div>

        <div id="modalTemplateContainer" style="margin-bottom:12px;display:none">
            <span style="font-size:12px;color:#166534;font-weight:600">Step 1: Start with Teacher's Template</span><br>
            <a id="modalTemplateLink" href="#" target="_blank" class="btn btn-secondary btn-sm" style="margin-top:4px;display:inline-flex;align-items:center;gap:6px;color:#0f9d58">
                <i class="fab fa-google-drive"></i> Open & Make a Copy in Google Docs/Sheets ↗
            </a>
        </div>

        <div class="form-group" style="margin-bottom:8px">
            <label style="font-size:12px;font-weight:700;color:#166534" id="googleUrlLabel">
                Step 2: Paste Your Shared Google Docs or Google Sheets Link *
            </label>
            <input type="url" name="google_doc_url" id="submitGoogleUrl" class="form-control" placeholder="https://docs.google.com/document/d/... or /spreadsheets/d/...">
            <small style="color:#166534;font-size:11px;display:block;margin-top:4px">
                <i class="fas fa-lock-open"></i> Make sure your document link sharing is set to <strong>"Anyone with the link can view"</strong> so your teacher can review it.
            </small>
        </div>

        <div style="background:#fff;border-left:3px solid #10b981;border-radius:4px;padding:8px 12px;font-size:11px;color:#065f46;margin-top:8px">
            <i class="fas fa-bolt" style="color:#10b981;margin-right:4px"></i>
            <strong>Instant Auto-Grading:</strong> Your submission will be recorded and graded immediately upon clicking submit! No waiting for manual grading. Your teacher can still review your live document and adjust comments anytime.
        </div>
    </div>

    <!-- Local File Upload Container -->
    <div id="fileGroup" class="form-group" style="display:none">
        <label>Upload File <span style="color:var(--text3);font-weight:400">(PDF, DOCX, XLSX, max 8MB)</span></label>
        <input type="file" name="submission_file" id="submissionFile" class="form-control" onchange="checkSubFileSize(this)">
        <div id="subFileSizeWarning" style="color:var(--danger);font-size:12px;margin-top:6px;display:none">This file is bigger than 8MB and may fail to upload on this server. Try a smaller file.</div>
    </div>

    <!-- Written Answer / Notes -->
    <div id="textGroup" class="form-group">
        <label id="textLabel">Your Answer / Notes</label>
        <textarea name="text_answer" id="submitTextAnswer" class="form-control" rows="3" placeholder="Type your answer or add notes for your teacher..."></textarea>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('submitModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit & Auto-Grade</button>
</div>
</form></div></div>

</div></div></div>
<script>
var BASE_URL = <?= json_encode(BASE_URL) ?>;
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}

function submitAssessment(a){
    document.getElementById('submitAssId').value = a.id;
    document.getElementById('submitModalTitle').textContent = 'Submit: ' + a.title;
    
    let isGoogle = (!a.submission_type || a.submission_type === 'google_docs_sheets');
    let isFile = (a.submission_type === 'file');

    let materialsLink = '';
    if (a.attachment_path) {
        materialsLink = `<br><a href="${BASE_URL}uploads/assessments/${a.attachment_path}" target="_blank" style="margin-top:6px;display:inline-block"><i class="fas fa-paperclip"></i> View teacher instructions/materials file</a>`;
    }
    document.getElementById('submitDesc').innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
            <div>
                <strong style="font-size:14px">${a.title}</strong>
                <p style="font-size:12px;color:var(--text2);margin:4px 0">${a.description || 'No additional instructions provided.'}</p>
            </div>
            <span class="badge badge-blue">Max: ${a.max_score} pts</span>
        </div>
        ${materialsLink}
    `;

    // Google template container
    var templateBox = document.getElementById('modalTemplateContainer');
    var templateLink = document.getElementById('modalTemplateLink');
    if (a.google_template_url) {
        templateBox.style.display = 'block';
        templateLink.href = a.google_template_url;
    } else {
        templateBox.style.display = 'none';
    }

    // Populate existing values if resubmitting
    var googleInput = document.getElementById('submitGoogleUrl');
    googleInput.value = a.google_doc_url || '';

    // Toggle fields based on mode
    var googleGroup = document.getElementById('googleGroup');
    var fileGroup = document.getElementById('fileGroup');
    var textLabel = document.getElementById('textLabel');

    if (isGoogle) {
        googleGroup.style.display = 'block';
        googleInput.required = true;
        fileGroup.style.display = 'none';
        textLabel.textContent = 'Additional Notes or Comments (optional)';
    } else if (isFile) {
        googleGroup.style.display = 'none';
        googleInput.required = false;
        fileGroup.style.display = 'block';
        textLabel.textContent = 'Submission Notes (optional)';
    } else {
        googleGroup.style.display = 'none';
        googleInput.required = false;
        fileGroup.style.display = 'none';
        textLabel.textContent = 'Your Written Response *';
        document.getElementById('submitTextAnswer').required = true;
    }

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
    if(f && f.files && f.files[0] && f.files[0].size > 25*1024*1024){
        alert('This file is way too large (over 25MB) and will not upload. Please choose a smaller file.');
        return false;
    }
    const g=document.getElementById('submitGoogleUrl');
    if(g && g.required && g.value.trim() !== '') {
        var v = g.value.trim();
        if(!v.includes('docs.google.com') && !v.includes('drive.google.com')) {
            alert('Please enter a valid Google Docs or Google Sheets URL (must contain docs.google.com or drive.google.com).');
            return false;
        }
    }
    return true;
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>
