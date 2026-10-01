<?php
require_once '../includes/config.php';
requireRole('student');
$pageTitle = 'Learning Materials';
$stid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_activity') {
    $assessmentId = !empty($_POST['assessment_id']) ? (int)$_POST['assessment_id'] : null;
    $matId = (int)$_POST['material_id'];
    $textAnswer = sanitize($_POST['text_answer'] ?? '');
    $filePath = null;

    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/submissions/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'pptx'];
        $fileInfo = pathinfo($_FILES['submission_file']['name']);
        $ext = strtolower($fileInfo['extension'] ?? '');

        if (in_array($ext, $allowedExtensions) && $_FILES['submission_file']['size'] <= 10 * 1024 * 1024) {
            $fname = 'sub_' . $stid . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $uploadDir . $fname)) {
                $filePath = $fname;
            }
        } else {
            setFlash('error', 'Invalid file type or file too large (max 10MB).');
            redirect(BASE_URL . 'student/materials.php');
        }
    }

    // Verify the assessment actually exists, that this student is enrolled
    // in the syllabus it belongs to, AND that it isn't closed (manually by
    // the teacher, or automatically because the due date has passed) —
    // before inserting a submission. This is checked server-side so a
    // student can't submit late just by re-posting the form directly.
    $validAssessment = false;
    $closedReason = null;
    if ($assessmentId) {
        $check = $conn->prepare("
            SELECT a.id, a.is_closed, a.due_date
            FROM assessments a
            JOIN enrollments e ON e.syllabus_id = a.syllabus_id
            WHERE a.id = ? AND e.student_id = ? AND e.status = 'enrolled'
            LIMIT 1
        ");
        $check->bind_param('ii', $assessmentId, $stid);
        $check->execute();
        $res = $check->get_result();
        $assessmentRow = $res->fetch_assoc();
        $check->close();

        if ($assessmentRow) {
            $manuallyClosed = (int)$assessmentRow['is_closed'] === 1;
            $duePassed = !empty($assessmentRow['due_date']) && strtotime($assessmentRow['due_date']) < time();

            if ($manuallyClosed) {
                $closedReason = 'This activity has been closed by your instructor.';
            } elseif ($duePassed) {
                $closedReason = 'The deadline for this activity has passed.';
            } else {
                $validAssessment = true;
            }
        }
    }

    if ($closedReason) {
        setFlash('error', $closedReason);
        redirect(BASE_URL . 'student/materials.php');
    }

    if ($validAssessment) {
        // Prevent duplicate submissions for the same assessment
        $dupCheck = $conn->prepare("SELECT id FROM submissions WHERE assessment_id = ? AND student_id = ? LIMIT 1");
        $dupCheck->bind_param('ii', $assessmentId, $stid);
        $dupCheck->execute();
        $dupResult = $dupCheck->get_result();

        if ($dupResult->num_rows > 0) {
            setFlash('error', 'You have already submitted this activity.');
        } else {
            $stmt = $conn->prepare("INSERT INTO submissions (assessment_id, student_id, file_path, text_answer, status) VALUES (?, ?, ?, ?, 'submitted')");
            $stmt->bind_param('iiss', $assessmentId, $stid, $filePath, $textAnswer);
            if ($stmt->execute()) {
                setFlash('success', 'Activity submitted successfully.');
            } else {
                setFlash('error', 'Something went wrong while saving your submission. Please try again.');
            }
            $stmt->close();
        }
        $dupCheck->close();
    } else {
        // This is the case that was silently failing before:
        // assessment_id was 0/null/invalid because the material-to-assessment
        // title match failed, or the student isn't enrolled in that syllabus.
        setFlash('error', 'This activity could not be linked to a valid assessment. Please contact your instructor.');
    }

    redirect(BASE_URL . 'student/materials.php');
}

$sylFilter = (int)($_GET['syl'] ?? 0);

$sylStmt = $conn->prepare("SELECT s.*, c.course_code, c.course_name
                            FROM enrollments e
                            JOIN syllabi s ON e.syllabus_id = s.id
                            JOIN courses c ON s.course_id = c.id
                            WHERE e.student_id = ? AND e.status = 'enrolled'");
$sylStmt->bind_param('i', $stid);
$sylStmt->execute();
$mySyllabi = $sylStmt->get_result();
$sylArr = [];
while ($s = $mySyllabi->fetch_assoc()) $sylArr[] = $s;

$where = "e.student_id = ? AND e.status = 'enrolled'";
$types = 'i';
$params = [$stid];

if ($sylFilter) {
    $where .= " AND m.syllabus_id = ?";
    $types .= 'i';
    $params[] = $sylFilter;
}

$sql = "SELECT m.*, c.course_code, st.topic_title, a.id as assessment_id,
               a.due_date as assessment_due_date, a.is_closed as assessment_is_closed,
               sub.id as submission_id,
               (SELECT COALESCE(tp.read_percentage, 0.00) FROM topic_progress tp WHERE tp.syllabus_topic_id = m.syllabus_topic_id AND tp.student_id = $stid LIMIT 1) as read_percentage
        FROM learning_materials m
        LEFT JOIN syllabi s ON m.syllabus_id = s.id
        LEFT JOIN courses c ON s.course_id = c.id
        LEFT JOIN syllabus_topics st ON m.syllabus_topic_id = st.id
        LEFT JOIN enrollments e ON e.syllabus_id = m.syllabus_id
        LEFT JOIN assessments a ON (a.syllabus_id = m.syllabus_id AND a.title = m.title)
        LEFT JOIN submissions sub ON (sub.assessment_id = a.id AND sub.student_id = ?)
        WHERE $where
        GROUP BY m.id
        ORDER BY m.created_at DESC";

// student_id for submissions join goes first, then the $where params
$types = 'i' . $types;
array_unshift($params, $stid);

$materialsStmt = $conn->prepare($sql);
$materialsStmt->bind_param($types, ...$params);
$materialsStmt->execute();
$materials = $materialsStmt->get_result();

$icons = ['document'=>'fa-file-pdf','video'=>'fa-video','link'=>'fa-link','presentation'=>'fa-file-powerpoint','quiz'=>'fa-question-circle','activity'=>'fa-pencil-alt','module'=>'fa-book-reader'];
$colors = ['document'=>'badge-red','video'=>'badge-blue','link'=>'badge-gray','presentation'=>'badge-orange','quiz'=>'badge-green','activity'=>'badge-purple','module'=>'badge-blue'];
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header"><h2>Learning Materials & Activities</h2></div>

<?php if ($flash = getFlash()): ?>
<div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?>">
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px"><div class="card-body">
<form method="GET"><select name="syl" class="form-control" onchange="this.form.submit()">
<option value="">All Courses</option>
<?php foreach($sylArr as $s): ?><option value="<?= $s['id'] ?>" <?= $sylFilter==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['course_code'].' - '.$s['course_name']) ?></option><?php endforeach; ?>
</select></form></div></div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
<?php while($m=$materials->fetch_assoc()): ?>
<?php
    $ext = $m['file_path'] ? strtolower(pathinfo($m['file_path'], PATHINFO_EXTENSION)) : '';
    $fileUrl = $m['file_path'] ? BASE_URL . 'uploads/materials/' . $m['file_path'] : '';
?>
<div class="card"><div class="card-body">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
        <span class="badge <?= $colors[$m['type']] ?? 'badge-gray' ?>"><i class="fas <?= $icons[$m['type']] ?? 'fa-file' ?>"></i> <?= ucfirst($m['type']) ?></span>
        <?php if ($m['type'] !== 'activity'): ?>
            <span class="badge <?= (float)$m['read_percentage'] >= 90 ? 'badge-green' : ((float)$m['read_percentage'] > 0 ? 'badge-blue' : 'badge-gray') ?>" style="font-size:11px">
                <i class="fas <?= (float)$m['read_percentage'] >= 90 ? 'fa-check-circle' : 'fa-book-reader' ?>"></i> <?= (float)$m['read_percentage'] > 0 ? round((float)$m['read_percentage']).'% Read' : 'Not Started' ?>
            </span>
        <?php endif; ?>
    </div>
    <h4 style="margin:8px 0"><?= safeHtml($m['title']) ?></h4>
    <p style="font-size:12px; color:var(--text3); margin-bottom: 12px;"><?= htmlspecialchars($m['course_code'] ?? '') ?><?= !empty($m['topic_title']) ? ' &bull; '.safeHtml($m['topic_title']) : '' ?></p>

    <div style="display:flex; flex-wrap: wrap; gap:6px">
        <?php if($m['type'] !== 'activity'): ?>
        <a href="<?= BASE_URL ?>student/read_material.php?id=<?= $m['id'] ?>" class="btn btn-primary btn-sm">
            <i class="fas <?= $m['type']==='video' ? 'fa-play-circle' : ($m['type']==='module' ? 'fa-book-reader' : ($m['type']==='link' ? 'fa-external-link-alt' : 'fa-book-open')) ?>"></i>
            <?= $m['type']==='video' ? 'Watch Lesson' : ($m['type']==='module' ? 'Read Module' : ($m['type']==='link' ? 'Open Resource' : 'Study Material')) ?>
        </a>
        <?php endif; ?>
        <?php if($m['file_path']): ?>
        <a href="<?= htmlspecialchars($fileUrl) ?>" download class="btn btn-secondary btn-sm" title="Download File">
            <i class="fas fa-download"></i>
        </a>
        <?php endif; ?>
        <?php if($m['type'] === 'activity'):
            $manuallyClosed = (int)($m['assessment_is_closed'] ?? 0) === 1;
            $duePassed = !empty($m['assessment_due_date']) && strtotime($m['assessment_due_date']) < time();
            $isClosed = $manuallyClosed || $duePassed;
        ?>
            <?php if($m['submission_id']): ?>
                <button class="btn btn-secondary btn-sm" disabled><i class="fas fa-check"></i> Submitted</button>
            <?php elseif(!$m['assessment_id']): ?>
                <button class="btn btn-secondary btn-sm" disabled title="No assessment linked to this activity">
                    <i class="fas fa-exclamation-triangle"></i> Unavailable
                </button>
            <?php elseif($isClosed): ?>
                <button class="btn btn-secondary btn-sm" disabled>
                    <i class="fas fa-lock"></i> <?= $manuallyClosed ? 'Closed' : 'Deadline Passed' ?>
                </button>
            <?php else: ?>
                <button class="btn btn-success btn-sm" onclick="openSubmitModal(<?= (int)$m['assessment_id'] ?>)">Submit Activity</button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php if($m['type'] === 'activity' && !empty($m['assessment_due_date'])): ?>
    <p style="font-size:11px;color:var(--text3);margin-top:8px">
        <i class="fas fa-clock"></i> Due <?= date('M d, Y g:i A', strtotime($m['assessment_due_date'])) ?>
    </p>
    <?php endif; ?>
</div></div>
<?php endwhile; ?>
</div>
</div></div></div>

<div class="modal-overlay" id="submitModal">
    <div class="modal">
        <div class="modal-header"><h3>Submit Activity</h3><button class="modal-close" onclick="closeModal('submitModal')">&times;</button></div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit_activity">
            <input type="hidden" name="assessment_id" id="subAid">
            <div class="modal-body">
                <div class="form-group"><label>Text Answer</label><textarea name="text_answer" class="form-control" rows="3" required></textarea></div>
                <div class="form-group"><label>Upload File (Max 10MB)</label><input type="file" name="submission_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.docx,.pptx"></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Confirm Submission</button></div>
        </form>
    </div>
</div>

<!-- View / Preview Modal -->
<div class="modal-overlay" id="viewMatModal">
    <div class="modal" style="max-width:850px;width:90%">
        <div class="modal-header">
            <span class="modal-title" id="viewMatTitle">Preview</span>
            <button class="modal-close" onclick="closeModal('viewMatModal')">&times;</button>
        </div>
        <div class="modal-body" style="min-height:400px;max-height:75vh;overflow:auto">
            <div id="viewMatContent" style="width:100%;height:100%">
                <div style="text-align:center;padding:40px;color:var(--text3)"><i class="fas fa-spinner fa-spin"></i> Loading preview...</div>
            </div>
        </div>
        <div class="modal-footer">
            <a id="viewMatDownload" href="#" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-download"></i> Download</a>
        </div>
    </div>
</div>

<!-- PDF.js and Mammoth.js (docx -> HTML) loaded from cdnjs, used only for in-browser preview -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>

<script>
if (window['pdfjsLib']) {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
}

function openSubmitModal(aid){
    document.getElementById('subAid').value = aid;
    document.getElementById('submitModal').classList.add('open');
}
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
function openModal(id){ document.getElementById(id).classList.add('open'); }

function getExtType(ext){
    ext = (ext || '').toLowerCase();
    if (['jpg','jpeg','png','gif','webp'].includes(ext)) return 'image';
    if (ext === 'pdf') return 'pdf';
    if (ext === 'docx') return 'docx';
    return 'other';
}

async function openViewModal(fileUrl, ext, title){
    document.getElementById('viewMatTitle').textContent = title || 'Preview';
    document.getElementById('viewMatDownload').href = fileUrl;

    const content = document.getElementById('viewMatContent');
    content.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3)"><i class="fas fa-spinner fa-spin"></i> Loading preview...</div>';
    openModal('viewMatModal');

    const kind = getExtType(ext);

    try {
        if (kind === 'image') {
            content.innerHTML = `<img src="${fileUrl}" style="max-width:100%;display:block;margin:0 auto" alt="${title}">`;

        } else if (kind === 'pdf') {
            content.innerHTML = '<div id="pdfPages"></div>';
            const pagesContainer = document.getElementById('pdfPages');
            const loadingTask = pdfjsLib.getDocument(fileUrl);
            const pdf = await loadingTask.promise;

            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                const page = await pdf.getPage(pageNum);
                const viewport = page.getViewport({ scale: 1.3 });
                const canvas = document.createElement('canvas');
                canvas.style.display = 'block';
                canvas.style.margin = '0 auto 16px';
                canvas.style.maxWidth = '100%';
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                pagesContainer.appendChild(canvas);
                const ctx = canvas.getContext('2d');
                await page.render({ canvasContext: ctx, viewport: viewport }).promise;
            }

        } else if (kind === 'docx') {
            const response = await fetch(fileUrl);
            const arrayBuffer = await response.arrayBuffer();
            const result = await mammoth.convertToHtml({ arrayBuffer: arrayBuffer });
            content.innerHTML = `<div class="docx-preview" style="background:#fff;padding:24px;border-radius:6px;color:#111">${result.value}</div>`;

        } else {
            content.innerHTML = `
                <div style="text-align:center;padding:40px;color:var(--text3)">
                    <i class="fas fa-file" style="font-size:32px;margin-bottom:12px;display:block"></i>
                    Preview isn't available for this file type.<br>
                    <a href="${fileUrl}" target="_blank" class="btn btn-primary btn-sm" style="margin-top:12px;display:inline-block">
                        <i class="fas fa-download"></i> Download instead
                    </a>
                </div>`;
        }
    } catch (err) {
        console.error('Preview error:', err);
        content.innerHTML = `
            <div style="text-align:center;padding:40px;color:var(--text3)">
                Could not load preview.<br>
                <a href="${fileUrl}" target="_blank" class="btn btn-primary btn-sm" style="margin-top:12px;display:inline-block">
                    <i class="fas fa-download"></i> Download instead
                </a>
            </div>`;
    }
}

document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>