<?php
require_once '../includes/config.php';
requireRole('teacher');
$pageTitle = 'Learning Materials';
$tid = $_SESSION['user_id'];

// Ensure upload directory exists
$uploadDir = '../uploads/materials/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$allowedExt = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'zip'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $sylId = (int)$_POST['syllabus_id'];
        $topicId = !empty($_POST['topic_id']) ? (int)$_POST['topic_id'] : null;
        $title = sanitize($_POST['title']);
        $desc = sanitize($_POST['description']);
        $type = sanitize($_POST['type']);
        $url = sanitize($_POST['external_url']);
        $dm = sanitize($_POST['delivery_mode']);

        $maxScore = !empty($_POST['max_score']) ? (float)$_POST['max_score'] : 100.00;
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

        $filePath = null;

        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['material_file']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowedExt)) {
                $fname = uniqid('mat_') . '.' . $ext;
                if (move_uploaded_file($_FILES['material_file']['tmp_name'], $uploadDir . $fname)) {
                    $filePath = $fname;
                }
            } else {
                setFlash('error', 'Invalid file type.');
                redirect(BASE_URL . 'teacher/materials.php');
            }
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO learning_materials (syllabus_id, syllabus_topic_id, teacher_id, title, description, type, file_path, external_url, delivery_mode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iiissssss', $sylId, $topicId, $tid, $title, $desc, $type, $filePath, $url, $dm);
            $stmt->execute();
            $stmt->close();

            if ($type === 'activity') {
                $astmt = $conn->prepare("INSERT INTO assessments (syllabus_id, topic_id, teacher_id, title, description, type, max_score, due_date, delivery_mode) VALUES (?, ?, ?, ?, ?, 'activity', ?, ?, ?)");
                $astmt->bind_param('iiissdss', $sylId, $topicId, $tid, $title, $desc, $maxScore, $dueDate, $dm);
                $astmt->execute();
                $astmt->close();
            }

            $conn->commit();
            setFlash('success', 'Material uploaded successfully.');
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            if ($filePath && file_exists($uploadDir . $filePath)) unlink($uploadDir . $filePath);
            setFlash('error', 'Could not save material. Please try again.');
        }

        redirect(BASE_URL . 'teacher/materials.php');

    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $sylId = (int)$_POST['syllabus_id'];
        $topicId = !empty($_POST['topic_id']) ? (int)$_POST['topic_id'] : null;
        $title = sanitize($_POST['title']);
        $desc = sanitize($_POST['description']);
        $type = sanitize($_POST['type']);
        $url = sanitize($_POST['external_url']);
        $dm = sanitize($_POST['delivery_mode']);
        $maxScore = !empty($_POST['max_score']) ? (float)$_POST['max_score'] : 100.00;
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $removeFile = !empty($_POST['remove_file']);

        // Fetch the existing row first — we need the OLD title/syllabus_id/type
        // to find the matching assessment row (it's linked by title match, not FK),
        // and the old file_path in case we need to replace or remove it.
        $old = $conn->prepare("SELECT * FROM learning_materials WHERE id = ? AND teacher_id = ?");
        $old->bind_param('ii', $id, $tid);
        $old->execute();
        $oldRow = $old->get_result()->fetch_assoc();
        $old->close();

        if (!$oldRow) {
            setFlash('error', 'Material not found.');
            redirect(BASE_URL . 'teacher/materials.php');
        }

        $filePath = $oldRow['file_path'];
        $newFileUploaded = false;

        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['material_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExt)) {
                $fname = uniqid('mat_') . '.' . $ext;
                if (move_uploaded_file($_FILES['material_file']['tmp_name'], $uploadDir . $fname)) {
                    $filePath = $fname;
                    $newFileUploaded = true;
                }
            } else {
                setFlash('error', 'Invalid file type.');
                redirect(BASE_URL . 'teacher/materials.php');
            }
        } elseif ($removeFile) {
            $filePath = null;
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE learning_materials SET syllabus_id=?, syllabus_topic_id=?, title=?, description=?, type=?, file_path=?, external_url=?, delivery_mode=? WHERE id=? AND teacher_id=?");
            $stmt->bind_param('iissssssii', $sylId, $topicId, $title, $desc, $type, $filePath, $url, $dm, $id, $tid);
            $stmt->execute();
            $stmt->close();

            // Keep the assessments table in sync since it's linked to
            // learning_materials only by matching syllabus_id + title.
            $wasActivity = $oldRow['type'] === 'activity';
            $isActivity = $type === 'activity';

            // Find any existing assessment row that was linked to the OLD title/syllabus.
            $find = $conn->prepare("SELECT id FROM assessments WHERE syllabus_id = ? AND title = ? AND teacher_id = ? AND type = 'activity' LIMIT 1");
            $find->bind_param('isi', $oldRow['syllabus_id'], $oldRow['title'], $tid);
            $find->execute();
            $existingAssessment = $find->get_result()->fetch_assoc();
            $find->close();

            if ($isActivity && $existingAssessment) {
                // Still an activity, update the linked assessment (title may have changed).
                $u = $conn->prepare("UPDATE assessments SET syllabus_id=?, topic_id=?, title=?, description=?, max_score=?, due_date=?, delivery_mode=? WHERE id=?");
                $u->bind_param('iissdssi', $sylId, $topicId, $title, $desc, $maxScore, $dueDate, $dm, $existingAssessment['id']);
                $u->execute();
                $u->close();
            } elseif ($isActivity && !$existingAssessment) {
                // Newly turned into an activity — create the assessment row.
                $ins = $conn->prepare("INSERT INTO assessments (syllabus_id, topic_id, teacher_id, title, description, type, max_score, due_date, delivery_mode) VALUES (?, ?, ?, ?, ?, 'activity', ?, ?, ?)");
                $ins->bind_param('iiissdss', $sylId, $topicId, $tid, $title, $desc, $maxScore, $dueDate, $dm);
                $ins->execute();
                $ins->close();
            } elseif (!$isActivity && $existingAssessment) {
                // No longer an activity — remove the now-orphaned assessment.
                $del = $conn->prepare("DELETE FROM assessments WHERE id = ?");
                $del->bind_param('i', $existingAssessment['id']);
                $del->execute();
                $del->close();
            }

            $conn->commit();

            // Only delete the old physical file after the DB transaction succeeds.
            if (($newFileUploaded || $removeFile) && $oldRow['file_path'] && file_exists($uploadDir . $oldRow['file_path'])) {
                unlink($uploadDir . $oldRow['file_path']);
            }

            setFlash('success', 'Material updated successfully.');
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            if ($newFileUploaded && $filePath && file_exists($uploadDir . $filePath)) unlink($uploadDir . $filePath);
            error_log('[materials edit] ' . $e->getMessage());
            setFlash('error', 'Could not update material. Please try again.');
        }

        redirect(BASE_URL . 'teacher/materials.php');

    } elseif ($action === 'toggle_close') {
        $id = (int)$_POST['id'];

        // $id here is the learning_materials id. Find the matching assessment
        // (linked by title + syllabus_id, same pattern used elsewhere) and flip
        // its is_closed flag.
        $m = $conn->prepare("SELECT title, syllabus_id FROM learning_materials WHERE id = ? AND teacher_id = ? AND type = 'activity'");
        $m->bind_param('ii', $id, $tid);
        $m->execute();
        $matRow = $m->get_result()->fetch_assoc();
        $m->close();

        if ($matRow) {
            $a = $conn->prepare("SELECT id, is_closed FROM assessments WHERE syllabus_id = ? AND title = ? AND teacher_id = ? AND type = 'activity' LIMIT 1");
            $a->bind_param('isi', $matRow['syllabus_id'], $matRow['title'], $tid);
            $a->execute();
            $assessRow = $a->get_result()->fetch_assoc();
            $a->close();

            if ($assessRow) {
                $newState = $assessRow['is_closed'] ? 0 : 1;
                $u = $conn->prepare("UPDATE assessments SET is_closed = ? WHERE id = ?");
                $u->bind_param('ii', $newState, $assessRow['id']);
                $u->execute();
                $u->close();
                setFlash('success', $newState ? 'Activity closed. Students can no longer submit.' : 'Activity reopened.');
            } else {
                setFlash('error', 'No linked assessment found for this activity.');
            }
        } else {
            setFlash('error', 'Activity not found.');
        }

        redirect(BASE_URL . 'teacher/materials.php');

    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];

        $stmt = $conn->prepare("SELECT title, syllabus_id, type, file_path FROM learning_materials WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param('ii', $id, $tid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $conn->begin_transaction();
            try {
                $del = $conn->prepare("DELETE FROM learning_materials WHERE id = ? AND teacher_id = ?");
                $del->bind_param('ii', $id, $tid);
                $del->execute();
                $del->close();

                if ($row['type'] === 'activity') {
                    $delA = $conn->prepare("DELETE FROM assessments WHERE syllabus_id = ? AND title = ? AND teacher_id = ? AND type = 'activity'");
                    $delA->bind_param('isi', $row['syllabus_id'], $row['title'], $tid);
                    $delA->execute();
                    $delA->close();
                }

                $conn->commit();

                if ($row['file_path'] && file_exists($uploadDir . $row['file_path'])) {
                    unlink($uploadDir . $row['file_path']);
                }

                setFlash('success', 'Material deleted.');
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                setFlash('error', 'Could not delete material. Please try again.');
            }
        }

        redirect(BASE_URL . 'teacher/materials.php');
    }
}

$sylFilter = (int)($_GET['syl'] ?? 0);

$where = "m.teacher_id = ?";
$types = 'i';
$params = [$tid];

if ($sylFilter) {
    $where .= " AND m.syllabus_id = ?";
    $types .= 'i';
    $params[] = $sylFilter;
}

$sql = "SELECT m.*, c.course_code, c.course_name, st.topic_title,
               a.id AS assessment_id, a.due_date AS assessment_due_date, a.is_closed AS assessment_is_closed
        FROM learning_materials m
        LEFT JOIN syllabi s ON m.syllabus_id = s.id
        LEFT JOIN courses c ON s.course_id = c.id
        LEFT JOIN syllabus_topics st ON m.syllabus_topic_id = st.id
        LEFT JOIN assessments a ON (a.syllabus_id = m.syllabus_id AND a.title = m.title AND a.type = 'activity')
        WHERE $where
        ORDER BY m.created_at DESC";

$materialsStmt = $conn->prepare($sql);
$materialsStmt->bind_param($types, ...$params);
$materialsStmt->execute();
$materialsResult = $materialsStmt->get_result();
$materialsArr = [];
while ($row = $materialsResult->fetch_assoc()) $materialsArr[] = $row;

$sylStmt = $conn->prepare("SELECT s.*, c.course_code, c.course_name FROM syllabi s JOIN courses c ON s.course_id = c.id WHERE s.teacher_id = ? ORDER BY c.course_name");
$sylStmt->bind_param('i', $tid);
$sylStmt->execute();
$mySyllabi = $sylStmt->get_result();
$sylArr = [];
while ($s = $mySyllabi->fetch_assoc()) $sylArr[] = $s;

$icons = ['document' => 'fa-file-pdf', 'video' => 'fa-video', 'link' => 'fa-link', 'presentation' => 'fa-file-powerpoint', 'quiz' => 'fa-question-circle', 'activity' => 'fa-pencil-alt'];
$colors = ['document' => 'badge-red', 'video' => 'badge-blue', 'link' => 'badge-gray', 'presentation' => 'badge-orange', 'quiz' => 'badge-green', 'activity' => 'badge-purple'];

// Extensions we know how to preview inline. Everything else just gets a download link.
$imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$pdfExts = ['pdf'];
$docxExts = ['docx'];
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
    <?php require_once '../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php require_once '../includes/topbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div class="page-header-left"><h2>Learning Materials</h2><p>Upload and manage course files and links</p></div>
                <button class="btn btn-primary" onclick="openModal('addMatModal')"><i class="fas fa-plus"></i> Add Material</button>
            </div>


            <?php if ($flash = getFlash()): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
            <?php endif; ?>

            <div class="card" style="margin-bottom:20px">
                <div class="card-body" style="padding:14px 20px">
                    <form method="GET" style="display:flex;gap:12px">
                        <select name="syl" class="form-control" style="width:300px" onchange="this.form.submit()">
                            <option value="">All Syllabi</option>
                            <?php foreach($sylArr as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $sylFilter==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['course_code'].' - '.$s['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Title</th><th>Course</th><th>Topic</th><th>Type</th><th>Mode</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach($materialsArr as $m): ?>
                        <?php
                            $ext = $m['file_path'] ? strtolower(pathinfo($m['file_path'], PATHINFO_EXTENSION)) : '';
                            $fileUrl = $m['file_path'] ? BASE_URL . 'uploads/materials/' . $m['file_path'] : '';
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($m['title']) ?></strong><br><small class="text-muted"><?= htmlspecialchars(substr($m['description'] ?? '', 0, 60)) ?></small></td>
                            <td><?= htmlspecialchars($m['course_code'] ?? '-') ?></td>
                            <td><?= $m['topic_title'] ? htmlspecialchars(substr($m['topic_title'], 0, 30)) : '<span class="text-muted">General</span>' ?></td>
                            <td><span class="badge <?= $colors[$m['type']] ?? 'badge-gray' ?>"><i class="fas <?= $icons[$m['type']] ?? 'fa-file' ?>"></i> <?= ucfirst($m['type']) ?></span></td>
                            <td><?php $mc=['online'=>'mode-online','offline'=>'mode-face','both'=>'mode-blended']; echo '<span class="mode-pill '.$mc[$m['delivery_mode']].'">'.ucfirst($m['delivery_mode']).'</span>'; ?></td>
                            <td>
                                <?php if($m['type'] === 'activity' && $m['assessment_id']):
                                    $duePassed = !empty($m['assessment_due_date']) && strtotime($m['assessment_due_date']) < time();
                                    $manuallyClosed = (int)$m['assessment_is_closed'] === 1;
                                    if ($manuallyClosed): ?>
                                        <span class="badge badge-red">Closed</span>
                                    <?php elseif ($duePassed): ?>
                                        <span class="badge badge-orange">Overdue</span>
                                    <?php else: ?>
                                        <span class="badge badge-green">Open</span>
                                    <?php endif; ?>
                                    <?php if(!empty($m['assessment_due_date'])): ?>
                                        <br><small class="text-muted">Due <?= date('M d, Y g:i A', strtotime($m['assessment_due_date'])) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($m['created_at'])) ?></td>
                            <td>
                                <div class="action-btns">
                                    <?php if($m['file_path']): ?>
                                    <button type="button" class="btn btn-secondary btn-sm"
                                        onclick="openViewModal('<?= htmlspecialchars($fileUrl, ENT_QUOTES) ?>', '<?= $ext ?>', '<?= htmlspecialchars(addslashes($m['title']), ENT_QUOTES) ?>')"
                                        title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Download"><i class="fas fa-download"></i></a>
                                    <?php endif; ?>
                                    <?php if($m['external_url']): ?><a href="<?= htmlspecialchars($m['external_url']) ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-external-link-alt"></i></a><?php endif; ?>

                                    <?php if($m['type'] === 'activity' && $m['assessment_id']): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="toggle_close">
                                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                        <?php if((int)$m['assessment_is_closed'] === 1): ?>
                                            <button class="btn btn-secondary btn-sm" title="Reopen"><i class="fas fa-lock-open"></i></button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" title="Close submissions"><i class="fas fa-lock"></i></button>
                                        <?php endif; ?>
                                    </form>
                                    <?php endif; ?>

                                    <button type="button" class="btn btn-secondary btn-sm" title="Edit"
                                        onclick='openEditModal(<?= json_encode([
                                            "id" => $m["id"],
                                            "syllabus_id" => $m["syllabus_id"],
                                            "syllabus_topic_id" => $m["syllabus_topic_id"],
                                            "title" => $m["title"],
                                            "description" => $m["description"],
                                            "type" => $m["type"],
                                            "delivery_mode" => $m["delivery_mode"],
                                            "external_url" => $m["external_url"],
                                            "has_file" => (bool)$m["file_path"],
                                            "file_name" => $m["file_path"],
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <form method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this material?')">
                                        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $m['id'] ?>">
                                        <button class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Material Modal -->
            <div class="modal-overlay" id="addMatModal">
                <div class="modal" style="max-width:600px">
                    <div class="modal-header"><span class="modal-title">Add Learning Material</span><button class="modal-close" onclick="closeModal('addMatModal')">&times;</button></div>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        <div class="modal-body">
                            <div class="form-group"><label>Syllabus/Course</label>
                            <select name="syllabus_id" id="matSyl" class="form-control" required onchange="loadTopics(this.value, 'matTopic')">
                                <option value="">Select syllabus...</option>
                                <?php foreach($sylArr as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['course_code'].' - '.$s['course_name']) ?></option><?php endforeach; ?>
                            </select></div>
                            <div class="form-group"><label>Topic (optional)</label>
                            <select name="topic_id" id="matTopic" class="form-control"><option value="">General / Not linked to a topic</option></select></div>
                            <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                            <div class="form-row">
                                <div class="form-group"><label>Type</label>
                                <select name="type" id="matType" class="form-control" onchange="toggleActivityFields(this.value, 'activityFields')">
                                    <option>document</option><option>video</option><option>link</option><option>presentation</option><option>quiz</option><option>activity</option>
                                </select></div>
                                <div class="form-group"><label>Delivery Mode</label>
                                <select name="delivery_mode" class="form-control"><option>both</option><option>online</option><option>offline</option></select></div>
                            </div>

                            <div class="form-row" id="activityFields" style="display:none">
                                <div class="form-group"><label>Max Score</label><input type="number" step="0.01" name="max_score" class="form-control" value="100"></div>
                                <div class="form-group"><label>Due Date</label><input type="datetime-local" name="due_date" class="form-control"></div>
                            </div>

                            <div class="form-group"><label>Upload File</label><input type="file" name="material_file" class="form-control"></div>
                            <div class="form-group"><label>Or External URL</label><input type="url" name="external_url" class="form-control" placeholder="https://..."></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('addMatModal')">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Material</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Material Modal -->
            <div class="modal-overlay" id="editMatModal">
                <div class="modal" style="max-width:600px">
                    <div class="modal-header"><span class="modal-title">Edit Learning Material</span><button class="modal-close" onclick="closeModal('editMatModal')">&times;</button></div>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editId">
                        <div class="modal-body">
                            <div class="form-group"><label>Syllabus/Course</label>
                            <select name="syllabus_id" id="editSyl" class="form-control" required onchange="loadTopics(this.value, 'editTopic')">
                                <option value="">Select syllabus...</option>
                                <?php foreach($sylArr as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['course_code'].' - '.$s['course_name']) ?></option><?php endforeach; ?>
                            </select></div>
                            <div class="form-group"><label>Topic (optional)</label>
                            <select name="topic_id" id="editTopic" class="form-control"><option value="">General / Not linked to a topic</option></select></div>
                            <div class="form-group"><label>Title</label><input type="text" name="title" id="editTitle" class="form-control" required></div>
                            <div class="form-group"><label>Description</label><textarea name="description" id="editDesc" class="form-control" rows="2"></textarea></div>
                            <div class="form-row">
                                <div class="form-group"><label>Type</label>
                                <select name="type" id="editType" class="form-control" onchange="toggleActivityFields(this.value, 'editActivityFields')">
                                    <option>document</option><option>video</option><option>link</option><option>presentation</option><option>quiz</option><option>activity</option>
                                </select></div>
                                <div class="form-group"><label>Delivery Mode</label>
                                <select name="delivery_mode" id="editDm" class="form-control"><option>both</option><option>online</option><option>offline</option></select></div>
                            </div>

                            <div class="form-row" id="editActivityFields" style="display:none">
                                <div class="form-group"><label>Max Score</label><input type="number" step="0.01" name="max_score" id="editMaxScore" class="form-control" value="100"></div>
                                <div class="form-group"><label>Due Date</label><input type="datetime-local" name="due_date" id="editDueDate" class="form-control"></div>
                            </div>

                            <div class="form-group" id="editCurrentFileWrap">
                                <label>Current File</label>
                                <div id="editCurrentFile" style="font-size:13px;color:var(--text3)"></div>
                                <label style="margin-top:6px;display:flex;align-items:center;gap:6px;font-weight:normal">
                                    <input type="checkbox" name="remove_file" id="editRemoveFile" value="1"> Remove current file
                                </label>
                            </div>
                            <div class="form-group"><label>Replace File (optional)</label><input type="file" name="material_file" class="form-control"></div>
                            <div class="form-group"><label>Or External URL</label><input type="url" name="external_url" id="editUrl" class="form-control" placeholder="https://..."></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('editMatModal')">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Material</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- View / Preview Modal -->
            <div class="modal-overlay" id="viewMatModal">
                <div class="modal" style="max-width:850px;width:90%">
                    <div class="modal-header"><span class="modal-title" id="viewMatTitle">Preview</span><button class="modal-close" onclick="closeModal('viewMatModal')">&times;</button></div>
                    <div class="modal-body" style="min-height:400px;max-height:75vh;overflow:auto">
                        <div id="viewMatContent" style="width:100%;height:100%">
                            <div style="text-align:center;padding:40px;color:var(--text3)"><i class="fas fa-spinner fa-spin"></i> Loading preview...</div>
                        </div>
                    </div>
                </div>
            </div>

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

function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}

function toggleActivityFields(type, targetId){
    document.getElementById(targetId).style.display = (type === 'activity') ? 'flex' : 'none';
}

function loadTopics(sylId, targetSelectId, selectedTopicId){
    const sel = document.getElementById(targetSelectId);
    if(!sylId){ sel.innerHTML = '<option value="">General / Not linked to a topic</option>'; return; }
    fetch('../teacher/get_topics.php?syl='+sylId).then(r=>r.json()).then(data=>{
        sel.innerHTML = '<option value="">General / Not linked to a topic</option>';
        data.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = `Week ${t.week_number}: ${t.topic_title}`;
            if (selectedTopicId && String(t.id) === String(selectedTopicId)) opt.selected = true;
            sel.appendChild(opt);
        });
    });
}

// ---------- EDIT ----------
function openEditModal(data){
    document.getElementById('editId').value = data.id;
    document.getElementById('editSyl').value = data.syllabus_id;
    document.getElementById('editTitle').value = data.title;
    document.getElementById('editDesc').value = data.description || '';
    document.getElementById('editType').value = data.type;
    document.getElementById('editDm').value = data.delivery_mode;
    document.getElementById('editUrl').value = data.external_url || '';
    document.getElementById('editRemoveFile').checked = false;

    toggleActivityFields(data.type, 'editActivityFields');

    const fileWrap = document.getElementById('editCurrentFile');
    fileWrap.textContent = data.has_file ? data.file_name : 'No file attached';

    loadTopics(data.syllabus_id, 'editTopic', data.syllabus_topic_id);

    openModal('editMatModal');
}

// ---------- VIEW / PREVIEW ----------
function getExtType(ext){
    ext = (ext || '').toLowerCase();
    if (['jpg','jpeg','png','gif','webp'].includes(ext)) return 'image';
    if (ext === 'pdf') return 'pdf';
    if (ext === 'docx') return 'docx';
    return 'other';
}

async function openViewModal(fileUrl, ext, title){
    document.getElementById('viewMatTitle').textContent = title || 'Preview';
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
            content.innerHTML = `<div class="docx-preview" style="background:#fff;padding:24px;border-radius:6px">${result.value}</div>`;

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