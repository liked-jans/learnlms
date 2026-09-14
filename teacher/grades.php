<?php
require_once '../includes/config.php';
requireRole('teacher');
$pageTitle = 'Grades';
$tid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token expired or invalid. Please try again.');
        redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL.'teacher/grades.php');
    }
    $subId=(int)$_POST['submission_id']; $score=(float)$_POST['score']; $feedback=sanitize($_POST['feedback']);
    $stmt=$conn->prepare("UPDATE submissions SET score=?,feedback=?,status='graded',is_auto_graded=0,graded_at=NOW() WHERE id=?");
    $stmt->bind_param('dsi',$score,$feedback,$subId); $stmt->execute();
    setFlash('success','Grade updated successfully.');
    redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL.'teacher/grades.php');
}

$assFilter = $_GET['assessment'] ?? '';
$isAll = ($assFilter === 'all');
$assFilterInt = (int)$assFilter;

$assessment = null;
$submissions = null;
$allData = [];

if ($isAll) {
    $stmtList = $conn->prepare("SELECT a.*,c.course_code,c.course_name FROM assessments a LEFT JOIN syllabi s ON a.syllabus_id=s.id LEFT JOIN courses c ON s.course_id=c.id WHERE a.teacher_id=? ORDER BY a.created_at DESC");
    $stmtList->bind_param('i', $tid);
    $stmtList->execute();
    $assessmentsList = $stmtList->get_result();

    while ($a = $assessmentsList->fetch_assoc()) {
        $stmt2 = $conn->prepare("SELECT sub.*,u.full_name,u.email FROM submissions sub JOIN users u ON sub.student_id=u.id WHERE sub.assessment_id=? ORDER BY u.full_name ASC");
        $stmt2->bind_param('i', $a['id']);
        $stmt2->execute();
        $res = $stmt2->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        $allData[] = ['assessment' => $a, 'submissions' => $rows];
    }
} elseif ($assFilterInt) {
    $stmt = $conn->prepare("SELECT a.*,c.course_code,c.course_name FROM assessments a LEFT JOIN syllabi s ON a.syllabus_id=s.id LEFT JOIN courses c ON s.course_id=c.id WHERE a.id=? AND a.teacher_id=?");
    $stmt->bind_param('ii', $assFilterInt, $tid);
    $stmt->execute();
    $assessment = $stmt->get_result()->fetch_assoc();
    if (!$assessment) redirect(BASE_URL.'teacher/grades.php');

    $stmt2 = $conn->prepare("SELECT sub.*,u.full_name,u.email FROM submissions sub JOIN users u ON sub.student_id=u.id WHERE sub.assessment_id=? ORDER BY u.full_name ASC");
    $stmt2->bind_param('i', $assFilterInt);
    $stmt2->execute();
    $submissions = $stmt2->get_result();
}

$stmtMy = $conn->prepare("SELECT a.*,c.course_code FROM assessments a LEFT JOIN syllabi s ON a.syllabus_id=s.id LEFT JOIN courses c ON s.course_id=c.id WHERE a.teacher_id=? ORDER BY a.created_at DESC");
$stmtMy->bind_param('i', $tid);
$stmtMy->execute();
$myAssessments = $stmtMy->get_result();
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header">
    <div class="page-header-left"><h2>Grades & Submissions</h2></div>
</div>

<div class="card" style="margin-bottom:20px"><div class="card-body" style="padding:14px 20px">
<form method="GET" style="display:flex;gap:12px">
<select name="assessment" class="form-control" style="width:380px" onchange="this.form.submit()">
<option value="">-- Select Assessment --</option>
<option value="all" <?= $isAll ? 'selected' : '' ?>>-- All Assessments --</option>
<?php while($a=$myAssessments->fetch_assoc()): ?><option value="<?= $a['id'] ?>" <?= (!$isAll && $assFilterInt==$a['id'])?'selected':'' ?>><?= htmlspecialchars($a['course_code'].' - '.$a['title']) ?></option><?php endwhile; ?>
</select>
</form></div></div>

<?php if (!$isAll && $assFilterInt && isset($assessment)): ?>
<div class="card" style="margin-bottom:20px"><div class="card-body">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div>
            <h3 style="font-size:16px;font-weight:700"><?= htmlspecialchars($assessment['title']) ?></h3>
            <p style="font-size:13px;color:var(--text3);margin-top:2px"><?= $assessment['course_code'] ?> &bull; Max Score: <?= $assessment['max_score'] ?> &bull; <?= ucfirst($assessment['type']) ?></p>
        </div>
        <?php if(!empty($assessment['google_template_url'])): ?>
            <a href="<?= htmlspecialchars($assessment['google_template_url']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="color:#0f9d58;display:inline-flex;align-items:center;gap:6px">
                <i class="fab fa-google-drive"></i> Teacher Template ↗
            </a>
        <?php endif; ?>
    </div>
</div></div>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Student</th><th>Submitted</th><th>Submission / Link</th><th>Score</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php
$submissions->data_seek(0);
while($s=$submissions->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($s['full_name']) ?><br><small class="text-muted"><?= $s['email'] ?></small></td>
    <td><?= date('M d, Y g:i A', strtotime($s['submitted_at'])) ?></td>
    <td>
        <?php if(!empty($s['google_doc_url'])): ?>
            <a href="<?= htmlspecialchars($s['google_doc_url']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:5px;color:#0f9d58;font-weight:600">
                <i class="fab fa-google-drive"></i> Open Doc/Sheet ↗
            </a>
            <?php if($s['text_answer']): ?><br><small class="text-muted"><?= htmlspecialchars(substr($s['text_answer'],0,50)) ?></small><?php endif; ?>
        <?php elseif($s['file_path']): ?>
            <button type="button" class="btn btn-secondary btn-sm" onclick="openViewSubModal('<?= htmlspecialchars(BASE_URL.'uploads/materials/'.$s['file_path'], ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($s['full_name']), ENT_QUOTES) ?>')"><i class="fas fa-eye"></i> View File</button>
        <?php elseif($s['text_answer']): ?>
            <span style="font-size:12px"><?= htmlspecialchars(substr($s['text_answer'],0,80)) ?>...</span>
        <?php else: ?>
            <span class="text-muted">-</span>
        <?php endif; ?>
    </td>
    <td><?= $s['score'] !== null ? '<strong>'.$s['score'].'</strong>/'.$assessment['max_score'] : '<span class="text-muted">Not graded</span>' ?></td>
    <td>
        <span class="badge <?= $s['status']==='graded'?'badge-green':($s['status']==='late'?'badge-red':'badge-orange') ?>"><?= $s['status'] ?></span>
        <?php if(!empty($s['is_auto_graded'])): ?>
            <br><span class="badge badge-gray" style="font-size:10px;margin-top:3px;display:inline-flex;align-items:center;gap:3px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0">
                <i class="fas fa-robot"></i> Auto-Graded
            </span>
        <?php endif; ?>
    </td>
    <td>
        <button class="btn <?= $s['status']==='graded' ? 'btn-secondary' : 'btn-primary' ?> btn-sm" onclick='gradeSubmission(<?= json_encode($s) ?>, <?= (float)$assessment['max_score'] ?>)'>
            <i class="fas <?= $s['status']==='graded' ? 'fa-edit' : 'fa-star' ?>"></i> <?= $s['status']==='graded' ? 'Change Grade' : 'Grade' ?>
        </button>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table></div></div>

<?php elseif ($isAll): ?>
<div class="card"><div class="card-body">
    <p style="font-size:13px;color:var(--text3)">Showing all your assessments and student submissions.</p>
</div></div>
<?php foreach ($allData as $block):
    $a = $block['assessment'];
    $rows = $block['submissions'];
?>
<div class="card" style="margin-bottom:20px"><div class="card-body">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div>
            <h3 style="font-size:16px;font-weight:700"><?= htmlspecialchars($a['title']) ?></h3>
            <p style="font-size:13px;color:var(--text3);margin-top:2px"><?= htmlspecialchars($a['course_code']) ?> &bull; Max Score: <?= $a['max_score'] ?> &bull; <?= ucfirst($a['type']) ?></p>
        </div>
        <?php if(!empty($a['google_template_url'])): ?>
            <a href="<?= htmlspecialchars($a['google_template_url']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="color:#0f9d58;display:inline-flex;align-items:center;gap:6px">
                <i class="fab fa-google-drive"></i> Teacher Template ↗
            </a>
        <?php endif; ?>
    </div>
</div></div>
<div class="card" style="margin-bottom:24px"><div class="table-wrap"><table>
<thead><tr><th>Student</th><th>Submitted</th><th>Submission / Link</th><th>Score</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php if (empty($rows)): ?>
<tr><td colspan="6" class="text-muted" style="text-align:center">No submissions yet.</td></tr>
<?php else: foreach ($rows as $s): ?>
<tr>
    <td><?= htmlspecialchars($s['full_name']) ?><br><small class="text-muted"><?= $s['email'] ?></small></td>
    <td><?= date('M d, Y g:i A', strtotime($s['submitted_at'])) ?></td>
    <td>
        <?php if(!empty($s['google_doc_url'])): ?>
            <a href="<?= htmlspecialchars($s['google_doc_url']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:5px;color:#0f9d58;font-weight:600">
                <i class="fab fa-google-drive"></i> Open Doc/Sheet ↗
            </a>
            <?php if($s['text_answer']): ?><br><small class="text-muted"><?= htmlspecialchars(substr($s['text_answer'],0,50)) ?></small><?php endif; ?>
        <?php elseif($s['file_path']): ?>
            <button type="button" class="btn btn-secondary btn-sm" onclick="openViewSubModal('<?= htmlspecialchars(BASE_URL.'uploads/materials/'.$s['file_path'], ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($s['full_name']), ENT_QUOTES) ?>')"><i class="fas fa-eye"></i> View File</button>
        <?php elseif($s['text_answer']): ?>
            <span style="font-size:12px"><?= htmlspecialchars(substr($s['text_answer'],0,80)) ?>...</span>
        <?php else: ?>
            <span class="text-muted">-</span>
        <?php endif; ?>
    </td>
    <td><?= $s['score'] !== null ? '<strong>'.$s['score'].'</strong>/'.$a['max_score'] : '<span class="text-muted">Not graded</span>' ?></td>
    <td>
        <span class="badge <?= $s['status']==='graded'?'badge-green':($s['status']==='late'?'badge-red':'badge-orange') ?>"><?= $s['status'] ?></span>
        <?php if(!empty($s['is_auto_graded'])): ?>
            <br><span class="badge badge-gray" style="font-size:10px;margin-top:3px;display:inline-flex;align-items:center;gap:3px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0">
                <i class="fas fa-robot"></i> Auto-Graded
            </span>
        <?php endif; ?>
    </td>
    <td>
        <button class="btn <?= $s['status']==='graded' ? 'btn-secondary' : 'btn-primary' ?> btn-sm" onclick='gradeSubmission(<?= json_encode($s) ?>, <?= (float)$a['max_score'] ?>)'>
            <i class="fas <?= $s['status']==='graded' ? 'fa-edit' : 'fa-star' ?>"></i> <?= $s['status']==='graded' ? 'Change Grade' : 'Grade' ?>
        </button>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table></div></div>
<?php endforeach; ?>

<?php else: ?>
<div class="empty-state card"><div class="card-body"><i class="fas fa-star-half-alt"></i><h3>Select an assessment</h3><p>Choose an assessment above to view and grade submissions</p></div></div>
<?php endif; ?>

<div class="modal-overlay" id="gradeModal">
<div class="modal" style="max-width:560px">
<div class="modal-header"><span class="modal-title" id="gradeModalHeader">Grade / Override Submission</span><button class="modal-close" onclick="closeModal('gradeModal')">&times;</button></div>
<form method="POST">
<?= csrfField() ?>
<input type="hidden" name="submission_id" id="gradeSubId">
<div class="modal-body">
    <div id="gradeStudentInfo" style="margin-bottom:16px;padding:14px;background:var(--bg);border-radius:8px;border:1px solid var(--border)"></div>
    <div class="form-group"><label>Score (max: <span id="gradeMaxScore"><?= $assessment['max_score'] ?? 100 ?></span>)</label>
    <input type="number" name="score" id="gradeScore" class="form-control" min="0" max="<?= $assessment['max_score'] ?? 100 ?>" step="0.5" required></div>
    <div class="form-group"><label>Teacher Feedback & Comments</label><textarea name="feedback" id="gradeFeedback" class="form-control" rows="3" placeholder="Leave remarks for the student..."></textarea></div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('gradeModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save / Override Grade</button>
</div>
</form></div></div>

<!-- Submission View / Preview Modal -->
<div class="modal-overlay" id="viewSubModal">
    <div class="modal" style="max-width:850px;width:90%">
        <div class="modal-header">
            <span class="modal-title" id="viewSubTitle">Preview</span>
            <button class="modal-close" onclick="closeModal('viewSubModal')">&times;</button>
        </div>
        <div class="modal-body" style="min-height:400px;max-height:75vh;overflow:auto">
            <div id="viewSubContent" style="width:100%;height:100%">
                <div style="text-align:center;padding:40px;color:var(--text3)"><i class="fas fa-spinner fa-spin"></i> Loading preview...</div>
            </div>
        </div>
        <div class="modal-footer">
            <a id="viewSubDownload" href="#" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-download"></i> Download</a>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>
<script>
if (window['pdfjsLib']) {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
}
function getSubExtType(ext){
    ext = (ext || '').toLowerCase();
    if (['jpg','jpeg','png','gif','webp'].includes(ext)) return 'image';
    if (ext === 'pdf') return 'pdf';
    if (ext === 'docx') return 'docx';
    return 'other';
}
async function openViewSubModal(fileUrl, title){
    var ext = fileUrl.split('.').pop();
    document.getElementById('viewSubTitle').textContent = title || 'Submission';
    document.getElementById('viewSubDownload').href = fileUrl;
    var content = document.getElementById('viewSubContent');
    content.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3)"><i class="fas fa-spinner fa-spin"></i> Loading preview...</div>';
    openModal('viewSubModal');
    var kind = getSubExtType(ext);
    try {
        if (kind === 'image') {
            content.innerHTML = '<img src="' + fileUrl + '" style="max-width:100%;display:block;margin:0 auto" alt="' + title + '">';
        } else if (kind === 'pdf') {
            content.innerHTML = '<div id="subPdfPages"></div>';
            var pagesContainer = document.getElementById('subPdfPages');
            var pdf = await pdfjsLib.getDocument(fileUrl).promise;
            for (var pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                var page = await pdf.getPage(pageNum);
                var viewport = page.getViewport({ scale: 1.3 });
                var canvas = document.createElement('canvas');
                canvas.style.display = 'block';
                canvas.style.margin = '0 auto 16px';
                canvas.style.maxWidth = '100%';
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                pagesContainer.appendChild(canvas);
                var ctx = canvas.getContext('2d');
                await page.render({ canvasContext: ctx, viewport: viewport }).promise;
            }
        } else if (kind === 'docx') {
            var response = await fetch(fileUrl);
            var arrayBuffer = await response.arrayBuffer();
            var result = await mammoth.convertToHtml({ arrayBuffer: arrayBuffer });
            content.innerHTML = '<div class="docx-preview" style="background:#fff;padding:24px;border-radius:6px;color:#111">' + result.value + '</div>';
        } else {
            content.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3)"><i class="fas fa-file" style="font-size:32px;margin-bottom:12px;display:block"></i>Preview isn\'t available for this file type.<br><a href="' + fileUrl + '" target="_blank" class="btn btn-primary btn-sm" style="margin-top:12px;display:inline-block"><i class="fas fa-download"></i> Download instead</a></div>';
        }
    } catch (err) {
        content.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3)">Could not load preview.<br><a href="' + fileUrl + '" target="_blank" class="btn btn-primary btn-sm" style="margin-top:12px;display:inline-block"><i class="fas fa-download"></i> Download instead</a></div>';
    }
}
</script>

</div></div></div>
<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}

function gradeSubmission(s, maxScore){
    document.getElementById('gradeSubId').value=s.id;
    document.getElementById('gradeScore').value=s.score||'';
    document.getElementById('gradeFeedback').value=s.feedback||'';

    let infoHtml = `<strong>${s.full_name}</strong><br><small style="color:var(--text3)">${s.email}</small>`;
    if (s.google_doc_url) {
        infoHtml += `
            <div style="margin-top:10px;padding:8px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px">
                <span style="font-size:12px;color:#166534;font-weight:600"><i class="fab fa-google-drive"></i> Student Google Doc / Sheet:</span><br>
                <a href="${s.google_doc_url}" target="_blank" class="btn btn-secondary btn-sm" style="margin-top:4px;display:inline-flex;align-items:center;gap:6px;color:#0f9d58;font-weight:600">
                    <i class="fab fa-google-drive"></i> Open Live Document ↗
                </a>
            </div>
        `;
    }
    if (s.is_auto_graded && s.is_auto_graded != 0) {
        infoHtml += `
            <div style="margin-top:6px;font-size:11px;color:#059669">
                <i class="fas fa-robot"></i> <em>Currently Auto-Graded. You can change or override the score and remarks below.</em>
            </div>
        `;
    }
    document.getElementById('gradeStudentInfo').innerHTML = infoHtml;

    var max = (typeof maxScore !== 'undefined' && maxScore !== null) ? maxScore : 100;
    document.getElementById('gradeMaxScore').textContent = max;
    document.getElementById('gradeScore').max = max;

    document.getElementById('gradeModalHeader').textContent = (s.status === 'graded') ? 'Override / Edit Grade' : 'Grade Submission';

    openModal('gradeModal');
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>