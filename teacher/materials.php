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

        if ($topicId) {
            $pastCheck = checkPastWeeklySyllabiDone($sylId, $topicId);
            if (!$pastCheck['can_proceed']) {
                setFlash('error', $pastCheck['message']);
                redirect(BASE_URL . 'teacher/materials.php' . ($sylId ? '?syl=' . $sylId : ''));
            }
        }
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

        if ($type !== 'module') {
            $content = null;
            $estTime = 5;
        } else {
            $content = $_POST['content'] ?? null;
            $estTime = max(1, (int)($_POST['estimated_read_time'] ?? 5));
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO learning_materials (syllabus_id, syllabus_topic_id, teacher_id, title, description, content, estimated_read_time, type, file_path, external_url, delivery_mode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iiisssissss', $sylId, $topicId, $tid, $title, $desc, $content, $estTime, $type, $filePath, $url, $dm);
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

        if ($topicId) {
            $pastCheck = checkPastWeeklySyllabiDone($sylId, $topicId);
            if (!$pastCheck['can_proceed']) {
                setFlash('error', $pastCheck['message']);
                redirect(BASE_URL . 'teacher/materials.php' . ($sylId ? '?syl=' . $sylId : ''));
            }
        }
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

        if ($type !== 'module') {
            $content = null;
            $estTime = 5;
        } else {
            $content = isset($_POST['content']) ? $_POST['content'] : $oldRow['content'];
            $estTime = max(1, (int)($_POST['estimated_read_time'] ?? ($oldRow['estimated_read_time'] ?: 5)));
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE learning_materials SET syllabus_id=?, syllabus_topic_id=?, title=?, description=?, content=?, estimated_read_time=?, type=?, file_path=?, external_url=?, delivery_mode=? WHERE id=? AND teacher_id=?");
            $stmt->bind_param('iisssissssii', $sylId, $topicId, $title, $desc, $content, $estTime, $type, $filePath, $url, $dm, $id, $tid);
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
               a.id AS assessment_id, a.due_date AS assessment_due_date, a.is_closed AS assessment_is_closed,
               (SELECT COUNT(*) FROM enrollments WHERE syllabus_id = m.syllabus_id AND status = 'enrolled') as total_enrolled,
               (SELECT COUNT(*) FROM topic_progress tp JOIN enrollments e ON e.student_id = tp.student_id AND e.syllabus_id = m.syllabus_id AND e.status = 'enrolled' WHERE tp.syllabus_topic_id = m.syllabus_topic_id AND (tp.read_percentage >= 90 OR tp.status = 'completed')) as finished_count,
               (SELECT COUNT(*) FROM topic_progress tp JOIN enrollments e ON e.student_id = tp.student_id AND e.syllabus_id = m.syllabus_id AND e.status = 'enrolled' WHERE tp.syllabus_topic_id = m.syllabus_topic_id AND tp.read_percentage > 0 AND tp.read_percentage < 90 AND tp.status != 'completed') as reading_count,
               (SELECT AVG(COALESCE(tp.read_percentage, 0)) FROM enrollments e LEFT JOIN topic_progress tp ON tp.student_id = e.student_id AND tp.syllabus_topic_id = m.syllabus_topic_id WHERE e.syllabus_id = m.syllabus_id AND e.status = 'enrolled') as avg_read_pct
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

$icons = ['document' => 'fa-file-pdf', 'video' => 'fa-video', 'link' => 'fa-link', 'presentation' => 'fa-file-powerpoint', 'quiz' => 'fa-question-circle', 'activity' => 'fa-pencil-alt', 'module' => 'fa-book-reader'];
$colors = ['document' => 'badge-red', 'video' => 'badge-blue', 'link' => 'badge-gray', 'presentation' => 'badge-orange', 'quiz' => 'badge-green', 'activity' => 'badge-purple', 'module' => 'badge-blue'];

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
                <button class="btn btn-primary" onclick="openAddMaterialModal()"><i class="fas fa-plus"></i> Add Material</button>
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
                        <thead><tr><th>Title</th><th>Course</th><th>Topic</th><th>Type</th><th>Mode</th><th style="min-width:180px">Reading Progress</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach($materialsArr as $m): ?>
                        <?php
                            $ext = $m['file_path'] ? strtolower(pathinfo($m['file_path'], PATHINFO_EXTENSION)) : '';
                            $fileUrl = $m['file_path'] ? BASE_URL . 'uploads/materials/' . $m['file_path'] : '';
                            $isModule = ($m['type'] === 'module');
                            $totEnrolled = (int)($m['total_enrolled'] ?? 0);
                            $finCount = (int)($m['finished_count'] ?? 0);
                            $readCount = (int)($m['reading_count'] ?? 0);
                            $notStarted = max(0, $totEnrolled - ($finCount + $readCount));
                            $avgRead = round((float)($m['avg_read_pct'] ?? 0), 1);
                        ?>
                        <tr>
                            <td><strong><?= safeHtml($m['title']) ?></strong><br><small class="text-muted"><?= safeHtml(substr($m['description'] ?? '', 0, 60)) ?></small></td>
                            <td><?= htmlspecialchars($m['course_code'] ?? '-') ?></td>
                            <td><?= $m['topic_title'] ? safeHtml(substr($m['topic_title'], 0, 30)) : '<span class="text-muted">General</span>' ?></td>
                            <td><span class="badge <?= $colors[$m['type']] ?? 'badge-gray' ?>"><i class="fas <?= $icons[$m['type']] ?? 'fa-file' ?>"></i> <?= ucfirst($m['type']) ?></span></td>
                            <td><?php $mc=['online'=>'mode-online','offline'=>'mode-face','both'=>'mode-blended']; echo '<span class="mode-pill '.$mc[$m['delivery_mode']].'">'.ucfirst($m['delivery_mode']).'</span>'; ?></td>
                            <td>
                                <?php if ($isModule): ?>
                                    <div style="min-width:180px">
                                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;font-size:11px">
                                            <span style="font-weight:700;color:var(--text)"><i class="fas fa-chart-line" style="color:#2563eb"></i> <?= $avgRead ?>% Avg</span>
                                            <span style="color:var(--text3);font-size:10px"><?= $totEnrolled ?> Enrolled</span>
                                        </div>
                                        <div style="height:5px;background:#e2e8f0;border-radius:99px;overflow:hidden;margin-bottom:6px">
                                            <div style="height:100%;width:<?= min(100, $avgRead) ?>%;background:linear-gradient(90deg, #3b82f6, #10b981);border-radius:99px"></div>
                                        </div>
                                        <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:6px;font-size:10px">
                                            <span style="background:#ecfdf5;color:#065f46;padding:1px 5px;border-radius:4px;border:1px solid #a7f3d0;font-weight:600" title="Finished (90-100%)">
                                                <?= $finCount ?> Done
                                            </span>
                                            <span style="background:#eff6ff;color:#1e40af;padding:1px 5px;border-radius:4px;border:1px solid #bfdbfe;font-weight:600" title="Reading (1-89%)">
                                                <?= $readCount ?> Reading
                                            </span>
                                            <?php if ($notStarted > 0): ?>
                                            <span style="background:#f1f5f9;color:#475569;padding:1px 5px;border-radius:4px;border:1px solid #cbd5e1;font-weight:600" title="Not Started (0%)">
                                                <?= $notStarted ?> New
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" class="btn btn-outline btn-sm" style="font-size:11px;padding:2px 8px;width:100%;text-align:center"
                                            onclick="openStudentProgressModal(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['title']), ENT_QUOTES) ?>')">
                                            <i class="fas fa-users"></i> View Students
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:12px">—</span>
                                <?php endif; ?>
                            </td>
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
                                    <?php if($m['type'] === 'module' || !empty($m['content'])): ?>
                                    <a href="<?= BASE_URL ?>student/read_material.php?id=<?= $m['id'] ?>" target="_blank" class="btn btn-primary btn-sm" title="Read / Preview Module">
                                        <i class="fas fa-book-reader"></i>
                                    </a>
                                    <?php endif; ?>
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
                                            "content" => $m["content"] ?? "",
                                            "estimated_read_time" => (int)($m["estimated_read_time"] ?: 5),
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
                <div class="modal" style="max-width:820px;width:95%">
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
                            <select name="topic_id" id="matTopic" class="form-control"><option value="">General / Not linked to a topic</option></select>
                            <div id="matTopicLockNotice" style="display:none;margin-top:8px;padding:8px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;font-size:12px;color:#92400e"><i class="fas fa-lock"></i> <span id="matTopicLockMsg"></span></div>
                            </div>
                            <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                            <div class="form-row">
                                <div class="form-group"><label>Type</label>
                                <select name="type" id="matType" class="form-control" onchange="toggleMaterialTypeFields(this.value, 'activityFields', 'moduleFields')">
                                    <option value="module" selected>module (Full-Context Reading Module / Lesson Text)</option>
                                    <option value="document">document (PDF / DOCX / File Handout)</option>
                                    <option value="presentation">presentation (Lecture Slides / PPT)</option>
                                    <option value="video">video (Recorded Lecture / Demo Video)</option>
                                    <option value="link">link (Web Resource / Reference Link)</option>
                                </select>
                                </div>
                                <div class="form-group"><label>Delivery Mode</label>
                                <select name="delivery_mode" class="form-control"><option>both</option><option>online</option><option>offline</option></select></div>
                            </div>

                            <div class="form-row" id="activityFields" style="display:none">
                                <div class="form-group"><label>Max Score</label><input type="number" step="0.01" name="max_score" class="form-control" value="100"></div>
                                <div class="form-group"><label>Due Date</label><input type="datetime-local" name="due_date" class="form-control"></div>
                            </div>

                            <!-- Module Specific Fields -->
                            <div id="moduleFields" style="display:block">
                                <div class="form-group">
                                    <label>Estimated Read Time (Minutes)</label>
                                    <input type="number" name="estimated_read_time" id="matReadTime" class="form-control" value="5" min="1" max="180">
                                    <small class="text-muted">Expected student reading duration.</small>
                                </div>
                                <div class="form-group">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                                        <label style="margin:0;font-weight:600">Full-Context Lesson Body (Docs / Rich Text)</label>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="loadModuleTemplateIntoEditor('matContentEditor', 'matContent')" style="font-size:11px;padding:3px 10px">
                                            <i class="fas fa-magic"></i> Load Example Template
                                        </button>
                                    </div>
                                    <!-- Docs Rich Text Editor -->
                                    <div class="docs-editor-wrap">
                                        <div class="docs-toolbar">
                                            <select class="docs-toolbar-select" onchange="applyBlock(this, 'matContentEditor', 'matContent')">
                                                <option value="p">Normal Text</option>
                                                <option value="h2">Heading 1</option>
                                                <option value="h3">Heading 2</option>
                                                <option value="h4">Heading 3</option>
                                            </select>
                                            <div class="docs-toolbar-sep"></div>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('bold', 'matContentEditor', null, 'matContent')" title="Bold (Ctrl+B)"><i class="fas fa-bold"></i></button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('italic', 'matContentEditor', null, 'matContent')" title="Italic (Ctrl+I)"><i class="fas fa-italic"></i></button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('underline', 'matContentEditor', null, 'matContent')" title="Underline (Ctrl+U)"><i class="fas fa-underline"></i></button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('strikeThrough', 'matContentEditor', null, 'matContent')" title="Strikethrough"><i class="fas fa-strikethrough"></i></button>
                                            <div class="docs-toolbar-sep"></div>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('insertUnorderedList', 'matContentEditor', null, 'matContent')" title="Bulleted List"><i class="fas fa-list-ul"></i></button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('insertOrderedList', 'matContentEditor', null, 'matContent')" title="Numbered List"><i class="fas fa-list-ol"></i></button>
                                            <div class="docs-toolbar-sep"></div>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="insertCallout('matContentEditor', 'matContent')" title="Insert Callout Note Box"><i class="fas fa-lightbulb" style="color:#d97706;margin-right:4px"></i> Note Box</button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="insertTable('matContentEditor', 'matContent')" title="Insert Table"><i class="fas fa-table" style="color:#2563eb;margin-right:4px"></i> Table</button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="insertLink('matContentEditor', 'matContent')" title="Insert Link"><i class="fas fa-link"></i></button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('removeFormat', 'matContentEditor', null, 'matContent')" title="Clear Formatting"><i class="fas fa-eraser"></i></button>
                                            <div class="docs-toolbar-sep"></div>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('undo', 'matContentEditor', null, 'matContent')" title="Undo"><i class="fas fa-undo"></i></button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('redo', 'matContentEditor', null, 'matContent')" title="Redo"><i class="fas fa-redo"></i></button>
                                        </div>
                                        <div class="docs-editor-body" id="matContentEditor" contenteditable="true" data-placeholder="Type your lesson notes, concepts, and instructions here, or click 'Load Example Template'..."></div>
                                    </div>
                                    <textarea name="content" id="matContent" style="display:none"></textarea>
                                    <small class="text-muted">Format your lesson just like a document. Students read this in the distraction-free reader (scroll depth tracks reading completion 0% ➔ 100%).</small>
                                </div>
                            </div>

                            <div class="form-group"><label>Upload File (optional for Module, required for Document)</label><input type="file" name="material_file" class="form-control"></div>
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
                <div class="modal" style="max-width:820px;width:95%">
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
                            <select name="topic_id" id="editTopic" class="form-control"><option value="">General / Not linked to a topic</option></select>
                            <div id="editTopicLockNotice" style="display:none;margin-top:8px;padding:8px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;font-size:12px;color:#92400e"><i class="fas fa-lock"></i> <span id="editTopicLockMsg"></span></div>
                            </div>
                            <div class="form-group"><label>Title</label><input type="text" name="title" id="editTitle" class="form-control" required></div>
                            <div class="form-group"><label>Description</label><textarea name="description" id="editDesc" class="form-control" rows="2"></textarea></div>
                            <div class="form-row">
                                <div class="form-group"><label>Type</label>
                                <select name="type" id="editType" class="form-control" onchange="toggleMaterialTypeFields(this.value, 'editActivityFields', 'editModuleFields')">
                                     <option value="module">module (Full-Context Reading Module / Lesson Text)</option>
                                     <option value="document">document (PDF / DOCX / File Handout)</option>
                                     <option value="presentation">presentation (Lecture Slides / PPT)</option>
                                     <option value="video">video (Recorded Lecture / Demo Video)</option>
                                     <option value="link">link (Web Resource / Reference Link)</option>
                                 </select>
                                 <small class="text-muted" style="display:block;margin-top:4px"><i class="fas fa-info-circle"></i> Need a quiz or activity? Create it in the <a href="assessments.php" style="color:var(--primary);text-decoration:underline">Assessments tab</a>.</small>
                                 </div>
                                 <div class="form-group"><label>Delivery Mode</label>
                                 <select name="delivery_mode" id="editDm" class="form-control"><option>both</option><option>online</option><option>offline</option></select></div>
                            </div>

                            <div class="form-row" id="editActivityFields" style="display:none">
                                <div class="form-group"><label>Max Score</label><input type="number" step="0.01" name="max_score" id="editMaxScore" class="form-control" value="100"></div>
                                <div class="form-group"><label>Due Date</label><input type="datetime-local" name="due_date" id="editDueDate" class="form-control"></div>
                            </div>

                            <!-- Edit Module Specific Fields -->
                            <div id="editModuleFields" style="display:none">
                                <div class="form-group">
                                    <label>Estimated Read Time (Minutes)</label>
                                    <input type="number" name="estimated_read_time" id="editReadTime" class="form-control" value="5" min="1" max="180">
                                    <small class="text-muted">Expected student reading duration.</small>
                                </div>
                                <div class="form-group">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                                        <label style="margin:0;font-weight:600">Full-Context Lesson Body (Docs / Rich Text)</label>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="loadModuleTemplateIntoEditor('editContentEditor', 'editContent')" style="font-size:11px;padding:3px 10px">
                                            <i class="fas fa-magic"></i> Load Example Template
                                        </button>
                                    </div>
                                    <!-- Docs Rich Text Editor -->
                                    <div class="docs-editor-wrap">
                                        <div class="docs-toolbar">
                                            <select class="docs-toolbar-select" onchange="applyBlock(this, 'editContentEditor', 'editContent')">
                                                <option value="p">Normal Text</option>
                                                <option value="h2">Heading 1</option>
                                                <option value="h3">Heading 2</option>
                                                <option value="h4">Heading 3</option>
                                            </select>
                                            <div class="docs-toolbar-sep"></div>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('bold', 'editContentEditor', null, 'editContent')" title="Bold (Ctrl+B)"><i class="fas fa-bold"></i></button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('italic', 'editContentEditor', null, 'editContent')" title="Italic (Ctrl+I)"><i class="fas fa-italic"></i></button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('underline', 'editContentEditor', null, 'editContent')" title="Underline (Ctrl+U)"><i class="fas fa-underline"></i></button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('strikeThrough', 'editContentEditor', null, 'editContent')" title="Strikethrough"><i class="fas fa-strikethrough"></i></button>
                                            <div class="docs-toolbar-sep"></div>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('insertUnorderedList', 'editContentEditor', null, 'editContent')" title="Bulleted List"><i class="fas fa-list-ul"></i></button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('insertOrderedList', 'editContentEditor', null, 'editContent')" title="Numbered List"><i class="fas fa-list-ol"></i></button>
                                            <div class="docs-toolbar-sep"></div>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="insertCallout('editContentEditor', 'editContent')" title="Insert Callout Note Box"><i class="fas fa-lightbulb" style="color:#d97706;margin-right:4px"></i> Note Box</button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="insertTable('editContentEditor', 'editContent')" title="Insert Table"><i class="fas fa-table" style="color:#2563eb;margin-right:4px"></i> Table</button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="insertLink('editContentEditor', 'editContent')" title="Insert Link"><i class="fas fa-link"></i></button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('removeFormat', 'editContentEditor', null, 'editContent')" title="Clear Formatting"><i class="fas fa-eraser"></i></button>
                                            <div class="docs-toolbar-sep"></div>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('undo', 'editContentEditor', null, 'editContent')" title="Undo"><i class="fas fa-undo"></i></button>
                                            <button type="button" class="docs-toolbar-btn" onmousedown="event.preventDefault()" onclick="execCmd('redo', 'editContentEditor', null, 'editContent')" title="Redo"><i class="fas fa-redo"></i></button>
                                        </div>
                                        <div class="docs-editor-body" id="editContentEditor" contenteditable="true" data-placeholder="Type your lesson notes, concepts, and instructions here, or click 'Load Example Template'..."></div>
                                    </div>
                                    <textarea name="content" id="editContent" style="display:none"></textarea>
                                    <small class="text-muted">Format your lesson just like a document. Students read this in the distraction-free reader (scroll depth tracks reading completion 0% ➔ 100%).</small>
                                </div>
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

            <!-- Student Reading Progress Modal -->
            <div class="modal-overlay" id="studentProgressModal">
                <div class="modal" style="max-width:820px;width:95%">
                    <div class="modal-header">
                        <div>
                            <span class="modal-title" id="spmTitle"><i class="fas fa-users" style="color:#2563eb;margin-right:6px"></i> Student Reading Progress</span>
                            <div id="spmSubtitle" style="font-size:12px;color:var(--text3);margin-top:2px"></div>
                        </div>
                        <button class="modal-close" onclick="closeModal('studentProgressModal')">&times;</button>
                    </div>
                    <div class="modal-body" style="padding:20px;max-height:75vh;overflow-y:auto">
                        <!-- Summary Stats Card Deck -->
                        <div id="spmSummaryDeck" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));gap:10px;margin-bottom:18px">
                            <!-- Populated dynamically -->
                        </div>

                        <!-- Filter & Search Controls -->
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:10px;flex-wrap:wrap">
                            <input type="text" id="spmSearchInput" class="form-control" placeholder="Search student name or email..." style="max-width:280px;font-size:12px;height:34px" onkeyup="filterStudentProgressTable()">
                            <div style="display:flex;gap:6px" id="spmFilterBtns">
                                <button type="button" class="btn btn-secondary btn-sm active" onclick="filterByStatus('all', this)" style="font-size:11px">All</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="filterByStatus('completed', this)" style="font-size:11px"><i class="fas fa-check-circle" style="color:#10b981"></i> Finished</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="filterByStatus('in_progress', this)" style="font-size:11px"><i class="fas fa-book-reader" style="color:#2563eb"></i> Reading</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="filterByStatus('not_started', this)" style="font-size:11px"><i class="fas fa-clock" style="color:#64748b"></i> Not Started</button>
                            </div>
                        </div>

                        <!-- Student List Table -->
                        <div class="table-wrap" style="border:1px solid var(--border);border-radius:8px">
                            <table style="width:100%;border-collapse:collapse;font-size:13px" id="spmStudentsTable">
                                <thead>
                                    <tr style="background:#f8fafc;border-bottom:1px solid var(--border)">
                                        <th style="padding:10px 14px;text-align:left">Student Name</th>
                                        <th style="padding:10px 14px;text-align:left;width:220px">Reading Progress</th>
                                        <th style="padding:10px 14px;text-align:center;width:120px">Status</th>
                                        <th style="padding:10px 14px;text-align:left;width:160px">Last Read Date</th>
                                    </tr>
                                </thead>
                                <tbody id="spmStudentsList">
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('studentProgressModal')">Close</button>
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

const LEARNING_MODULE_TEMPLATE = `<h3>1. Overview & Core Purpose</h3>
<p>Provide a comprehensive executive overview of this week's lesson, introducing the foundational problem statement, key industry applications, and learning objectives.</p>

<div style="background:#f8fafc;border-left:4px solid #3b82f6;padding:16px 20px;margin:20px 0;border-radius:0 8px 8px 0">
    <h4 style="margin-top:0;color:#1e40af"><i class="fas fa-lightbulb"></i> Key Concept / Guiding Principle</h4>
    <p style="margin-bottom:0">Highlight the central architectural or academic takeaway that every student must understand before proceeding.</p>
</div>

<h3>2. Theoretical Principles & Deep-Dive</h3>
<p>Break down the core theory and methodology step-by-step:</p>
<ul>
    <li><strong>Core Component 1:</strong> Detailed explanation of the first foundational mechanism.</li>
    <li><strong>Core Component 2:</strong> Detailed explanation of the second foundational mechanism.</li>
    <li><strong>Core Component 3:</strong> Detailed explanation of the third foundational mechanism.</li>
</ul>

<h3>3. Comparison Matrix / Practical Framework</h3>
<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px">
    <thead>
        <tr style="background:#f1f5f9;text-align:left">
            <th style="padding:10px;border:1px solid #cbd5e1">Approach / Technique</th>
            <th style="padding:10px;border:1px solid #cbd5e1">Key Attributes</th>
            <th style="padding:10px;border:1px solid #cbd5e1">Best Use Cases</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="padding:10px;border:1px solid #cbd5e1;font-weight:bold">Methodology Alpha</td>
            <td style="padding:10px;border:1px solid #cbd5e1">Lightweight, rapid execution, low overhead.</td>
            <td style="padding:10px;border:1px solid #cbd5e1">Early discovery and proof-of-concept sprints.</td>
        </tr>
        <tr>
            <td style="padding:10px;border:1px solid #cbd5e1;font-weight:bold">Methodology Beta</td>
            <td style="padding:10px;border:1px solid #cbd5e1">Rigorous, contract-driven, exhaustive validation.</td>
            <td style="padding:10px;border:1px solid #cbd5e1">Production compliance and mission-critical systems.</td>
        </tr>
    </tbody>
</table>

<h3>4. Critical Best Practices & Takeaways</h3>
<p>Summarize practical rules of thumb, common pitfalls to avoid, and reflection questions for the upcoming assessment.</p>`;

function syncDocsEditor(editorId, textareaId) {
    const editor = document.getElementById(editorId);
    const textarea = document.getElementById(textareaId);
    if (editor && textarea) {
        textarea.value = editor.innerHTML;
    }
}

function execCmd(cmd, editorId, val = null, textareaId = null) {
    const editor = document.getElementById(editorId);
    if (!editor) return;
    editor.focus();
    document.execCommand(cmd, false, val);
    if (textareaId) syncDocsEditor(editorId, textareaId);
}

function applyBlock(selectEl, editorId, textareaId = null) {
    const tag = selectEl.value;
    const editor = document.getElementById(editorId);
    if (!editor) return;
    editor.focus();
    try {
        document.execCommand('formatBlock', false, '<' + tag + '>');
    } catch (e) {
        document.execCommand('formatBlock', false, tag);
    }
    if (textareaId) syncDocsEditor(editorId, textareaId);
}

function insertCallout(editorId, textareaId = null) {
    const editor = document.getElementById(editorId);
    if (!editor) return;
    editor.focus();
    const html = `<div style="background:#f8fafc;border-left:4px solid #2563eb;padding:14px 18px;margin:16px 0;border-radius:0 6px 6px 0"><strong style="color:#1d4ed8"><i class="fas fa-lightbulb"></i> Key Concept / Note:</strong><p style="margin:6px 0 0 0">Enter your key takeaway or important concept here...</p></div><p><br></p>`;
    document.execCommand('insertHTML', false, html);
    if (textareaId) syncDocsEditor(editorId, textareaId);
}

function insertTable(editorId, textareaId = null) {
    const editor = document.getElementById(editorId);
    if (!editor) return;
    editor.focus();
    const html = `<table style="width:100%;border-collapse:collapse;margin:14px 0">
        <thead>
            <tr style="background:#f1f5f9">
                <th style="border:1px solid #cbd5e1;padding:8px 12px;text-align:left">Approach / Technique</th>
                <th style="border:1px solid #cbd5e1;padding:8px 12px;text-align:left">Key Attributes</th>
                <th style="border:1px solid #cbd5e1;padding:8px 12px;text-align:left">Best Use Cases</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border:1px solid #cbd5e1;padding:8px 12px;font-weight:600">Alpha Option</td>
                <td style="border:1px solid #cbd5e1;padding:8px 12px">Lightweight, rapid execution, low overhead.</td>
                <td style="border:1px solid #cbd5e1;padding:8px 12px">Initial prototype & discovery phase.</td>
            </tr>
            <tr>
                <td style="border:1px solid #cbd5e1;padding:8px 12px;font-weight:600">Beta Option</td>
                <td style="border:1px solid #cbd5e1;padding:8px 12px">Rigorous, contract-driven, exhaustive validation.</td>
                <td style="border:1px solid #cbd5e1;padding:8px 12px">Production compliance & mission-critical systems.</td>
            </tr>
        </tbody>
    </table><p><br></p>`;
    document.execCommand('insertHTML', false, html);
    if (textareaId) syncDocsEditor(editorId, textareaId);
}

function insertLink(editorId, textareaId = null) {
    const editor = document.getElementById(editorId);
    if (!editor) return;
    const url = prompt('Enter the link URL (e.g. https://...):', 'https://');
    if (url && url.trim() !== '' && url !== 'https://') {
        editor.focus();
        document.execCommand('createLink', false, url.trim());
        if (textareaId) syncDocsEditor(editorId, textareaId);
    }
}

function loadModuleTemplateIntoEditor(editorId, textareaId) {
    const editor = document.getElementById(editorId);
    const textarea = document.getElementById(textareaId);
    if (!editor) return;
    const text = editor.innerText.trim();
    if (text !== '' && !confirm('Replace current editor text with the standard Learning Module example template?')) {
        return;
    }
    editor.innerHTML = LEARNING_MODULE_TEMPLATE;
    if (textarea) textarea.value = LEARNING_MODULE_TEMPLATE;
}

function loadModuleTemplate(targetId) {
    if (targetId === 'matContent') {
        loadModuleTemplateIntoEditor('matContentEditor', 'matContent');
    } else if (targetId === 'editContent') {
        loadModuleTemplateIntoEditor('editContentEditor', 'editContent');
    } else {
        const el = document.getElementById(targetId);
        if (el) {
            if (el.value.trim() !== '' && !confirm('Replace current text with the standard Learning Module example template?')) return;
            el.value = LEARNING_MODULE_TEMPLATE;
        }
    }
}

function setupEditorSync(editorId, textareaId) {
    const editor = document.getElementById(editorId);
    const textarea = document.getElementById(textareaId);
    if (!editor || !textarea) return;
    const sync = () => { textarea.value = editor.innerHTML; };
    editor.addEventListener('input', sync);
    editor.addEventListener('blur', sync);
    editor.addEventListener('keyup', sync);
    editor.addEventListener('paste', () => { setTimeout(sync, 10); });
}

function toggleMaterialTypeFields(type, activityTargetId, moduleTargetId) {
    if (activityTargetId) {
        const act = document.getElementById(activityTargetId);
        if (act) act.style.display = (type === 'activity') ? 'flex' : 'none';
    }
    if (moduleTargetId) {
        const mod = document.getElementById(moduleTargetId);
        if (mod) mod.style.display = (type === 'module') ? 'block' : 'none';
        
        const isEdit = moduleTargetId.includes('edit');
        const editorId = isEdit ? 'editContentEditor' : 'matContentEditor';
        const textareaId = isEdit ? 'editContent' : 'matContent';
        const editor = document.getElementById(editorId);
        const textarea = document.getElementById(textareaId);

        if (type !== 'module') {
            if (textarea) textarea.value = '';
            if (editor) editor.innerHTML = '';
        } else {
            if (editor && (!editor.innerHTML || editor.innerHTML.trim() === '')) {
                if (textarea && textarea.value) {
                    editor.innerHTML = textarea.value;
                } else {
                    editor.innerHTML = LEARNING_MODULE_TEMPLATE;
                    if (textarea) textarea.value = LEARNING_MODULE_TEMPLATE;
                }
            }
        }
    }
}

function toggleActivityFields(type, targetId){
    toggleMaterialTypeFields(type, targetId, null);
}

function openAddMaterialModal() {
    const type = document.getElementById('matType').value;
    toggleMaterialTypeFields(type, 'activityFields', 'moduleFields');
    const contentInput = document.getElementById('matContent');
    const contentEditor = document.getElementById('matContentEditor');
    if (type === 'module') {
        if ((!contentInput.value || contentInput.value.trim() === '') && (!contentEditor.innerHTML || contentEditor.innerHTML.trim() === '')) {
            contentEditor.innerHTML = LEARNING_MODULE_TEMPLATE;
            contentInput.value = LEARNING_MODULE_TEMPLATE;
        }
    } else {
        if (contentInput) contentInput.value = '';
        if (contentEditor) contentEditor.innerHTML = '';
    }
    openModal('addMatModal');
}

function loadTopics(sylId, targetSelectId, selectedTopicId){
    const sel = document.getElementById(targetSelectId);
    const noticeEl = document.getElementById(targetSelectId === 'matTopic' ? 'matTopicLockNotice' : 'editTopicLockNotice');
    const msgEl = document.getElementById(targetSelectId === 'matTopic' ? 'matTopicLockMsg' : 'editTopicLockMsg');
    const formEl = sel ? sel.closest('form') : null;
    const submitBtn = formEl ? formEl.querySelector('button[type="submit"]') : null;

    if (noticeEl) noticeEl.style.display = 'none';
    if (submitBtn) submitBtn.disabled = false;

    if(!sylId){ sel.innerHTML = '<option value="">General / Not linked to a topic</option>'; return; }
    fetch('../teacher/get_topics.php?syl='+sylId).then(r=>r.json()).then(data=>{
        sel.innerHTML = '<option value="">General / Not linked to a topic</option>';
        data.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = (t.can_proceed ? '' : '🔒 ') + `Week ${t.week_number}: ${t.topic_title}` + (t.can_proceed ? '' : ' (Past week not marked done)');
            opt.setAttribute('data-can-proceed', t.can_proceed ? '1' : '0');
            opt.setAttribute('data-lock-msg', t.lock_message || '');
            if (selectedTopicId && String(t.id) === String(selectedTopicId)) opt.selected = true;
            sel.appendChild(opt);
        });

        sel.onchange = function(e) {
            const selectedOpt = sel.options[sel.selectedIndex];
            const canProceed = selectedOpt ? selectedOpt.getAttribute('data-can-proceed') !== '0' : true;
            const lockMsg = selectedOpt ? selectedOpt.getAttribute('data-lock-msg') : '';
            if (!canProceed && lockMsg) {
                if (noticeEl && msgEl) {
                    msgEl.textContent = lockMsg;
                    noticeEl.style.display = 'block';
                }
                if (submitBtn) submitBtn.disabled = true;
                if (e && e.isTrusted && window.showWeeklyLockAlert) {
                    window.showWeeklyLockAlert(lockMsg);
                }
            } else {
                if (noticeEl) noticeEl.style.display = 'none';
                if (submitBtn) submitBtn.disabled = false;
            }
        };
        sel.onchange();
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

    // Populate module content into both textarea and visual rich-text Docs editor
    const editContent = document.getElementById('editContent');
    if (editContent) editContent.value = data.content || '';
    const editContentEditor = document.getElementById('editContentEditor');
    if (editContentEditor) editContentEditor.innerHTML = data.content || '';

    const editReadTime = document.getElementById('editReadTime');
    if (editReadTime) editReadTime.value = data.estimated_read_time || 5;

    toggleMaterialTypeFields(data.type, 'editActivityFields', 'editModuleFields');

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

/* ===== STUDENT READING PROGRESS MODAL JS ===== */
let currentProgressStudents = [];
let currentFilterStatus = 'all';

function openStudentProgressModal(materialId, materialTitle) {
    document.getElementById('spmTitle').innerHTML = '<i class="fas fa-users" style="color:#2563eb;margin-right:6px"></i> Student Reading Progress';
    document.getElementById('spmSubtitle').textContent = 'Loading progress for: ' + materialTitle + '...';
    document.getElementById('spmSummaryDeck').innerHTML = '<div style="text-align:center;padding:20px;grid-column:1/-1;color:var(--text3)"><i class="fas fa-spinner fa-spin"></i> Fetching student progress metrics...</div>';
    document.getElementById('spmStudentsList').innerHTML = '<tr><td colspan="4" style="text-align:center;padding:30px;color:var(--text3)"><i class="fas fa-spinner fa-spin"></i> Loading students...</td></tr>';
    document.getElementById('spmSearchInput').value = '';
    currentFilterStatus = 'all';
    document.querySelectorAll('#spmFilterBtns button').forEach(b => b.classList.remove('active', 'btn-primary'));
    if (document.querySelector('#spmFilterBtns button')) {
        document.querySelector('#spmFilterBtns button').classList.add('active');
    }
    openModal('studentProgressModal');

    fetch('../teacher/get_material_progress.php?id=' + materialId)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                document.getElementById('spmStudentsList').innerHTML = '<tr><td colspan="4" style="text-align:center;color:#ef4444;padding:20px">' + (res.error || 'Failed to load progress') + '</td></tr>';
                return;
            }

            const m = res.material;
            const s = res.summary;
            document.getElementById('spmSubtitle').innerHTML = '<strong>' + escapeHtml(m.course_code) + '</strong> &bull; ' + (m.topic_title ? 'Week ' + m.week_number + ': ' + escapeHtml(m.topic_title) + ' &bull; ' : '') + escapeHtml(m.title);

            // Render summary cards
            document.getElementById('spmSummaryDeck').innerHTML = `
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;text-align:center">
                    <div style="font-size:22px;font-weight:700;color:#0f172a">${s.total_enrolled}</div>
                    <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase">Enrolled Students</div>
                </div>
                <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:12px;text-align:center">
                    <div style="font-size:22px;font-weight:700;color:#065f46">${s.finished_count}</div>
                    <div style="font-size:11px;color:#047857;font-weight:600;text-transform:uppercase">Finished (&ge;90%)</div>
                </div>
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px;text-align:center">
                    <div style="font-size:22px;font-weight:700;color:#1e40af">${s.reading_count}</div>
                    <div style="font-size:11px;color:#2563eb;font-weight:600;text-transform:uppercase">Reading (1-89%)</div>
                </div>
                <div style="background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;padding:12px;text-align:center">
                    <div style="font-size:22px;font-weight:700;color:#475569">${s.not_started_count}</div>
                    <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase">Not Started (0%)</div>
                </div>
                <div style="background:#fdf4ff;border:1px solid #f0abfc;border-radius:8px;padding:12px;text-align:center">
                    <div style="font-size:22px;font-weight:700;color:#86198f">${s.avg_pct}%</div>
                    <div style="font-size:11px;color:#a21caf;font-weight:600;text-transform:uppercase">Cohort Average</div>
                </div>
            `;

            currentProgressStudents = res.students || [];
            renderStudentsTable(currentProgressStudents);
        })
        .catch(err => {
            console.error(err);
            document.getElementById('spmStudentsList').innerHTML = '<tr><td colspan="4" style="text-align:center;color:#ef4444;padding:20px">Error connecting to server.</td></tr>';
        });
}

function renderStudentsTable(students) {
    const list = document.getElementById('spmStudentsList');
    if (!students || students.length === 0) {
        list.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:24px;color:var(--text3)">No students matching filter.</td></tr>';
        return;
    }

    let html = '';
    students.forEach(st => {
        let badgeHtml = '';
        let barColor = '#3b82f6';
        if (st.status === 'completed') {
            badgeHtml = '<span class="badge badge-green" style="font-size:11px"><i class="fas fa-check-circle"></i> Finished</span>';
            barColor = '#10b981';
        } else if (st.status === 'in_progress') {
            badgeHtml = '<span class="badge badge-blue" style="font-size:11px"><i class="fas fa-book-reader"></i> Reading</span>';
            barColor = '#3b82f6';
        } else {
            badgeHtml = '<span class="badge badge-gray" style="font-size:11px"><i class="fas fa-clock"></i> Not Started</span>';
            barColor = '#cbd5e1';
        }

        html += `
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:10px 14px">
                    <div style="font-weight:600;color:#0f172a">${escapeHtml(st.full_name)}</div>
                    <div style="font-size:11px;color:#64748b">${escapeHtml(st.email)}</div>
                </td>
                <td style="padding:10px 14px">
                    <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:3px">
                        <span style="font-weight:700;color:#334155">${st.read_pct}% Scrolled</span>
                    </div>
                    <div style="height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden">
                        <div style="height:100%;width:${Math.min(100, Math.max(0, st.read_pct))}%;background:${barColor};border-radius:99px"></div>
                    </div>
                </td>
                <td style="padding:10px 14px;text-align:center">${badgeHtml}</td>
                <td style="padding:10px 14px;font-size:12px;color:#64748b">${escapeHtml(st.last_read_formatted)}</td>
            </tr>
        `;
    });
    list.innerHTML = html;
}

function filterStudentProgressTable() {
    const term = (document.getElementById('spmSearchInput').value || '').toLowerCase().trim();
    const filtered = currentProgressStudents.filter(st => {
        const matchesStatus = (currentFilterStatus === 'all') || (st.status === currentFilterStatus);
        const matchesText = !term || (st.full_name && st.full_name.toLowerCase().includes(term)) || (st.email && st.email.toLowerCase().includes(term));
        return matchesStatus && matchesText;
    });
    renderStudentsTable(filtered);
}

function filterByStatus(status, btn) {
    currentFilterStatus = status;
    document.querySelectorAll('#spmFilterBtns button').forEach(b => b.classList.remove('active', 'btn-primary'));
    btn.classList.add('active');
    filterStudentProgressTable();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));

document.addEventListener('DOMContentLoaded', function() {
    setupEditorSync('matContentEditor', 'matContent');
    setupEditorSync('editContentEditor', 'editContent');

    const addForm = document.querySelector('#addMatModal form');
    if (addForm) {
        addForm.addEventListener('submit', function() {
            const type = document.getElementById('matType').value;
            if (type === 'module') {
                syncDocsEditor('matContentEditor', 'matContent');
            } else {
                document.getElementById('matContent').value = '';
            }
        });
    }

    const editForm = document.querySelector('#editMatModal form');
    if (editForm) {
        editForm.addEventListener('submit', function() {
            const type = document.getElementById('editType').value;
            if (type === 'module') {
                syncDocsEditor('editContentEditor', 'editContent');
            } else {
                document.getElementById('editContent').value = '';
            }
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('add') === '1') {
        const sylId = urlParams.get('syl');
        const topicId = urlParams.get('topic_id');
        if (sylId) {
            const sylSelect = document.getElementById('matSyl');
            if (sylSelect) sylSelect.value = sylId;
            loadTopics(sylId, 'matTopic', topicId);
        }
        openAddMaterialModal();
    }
});
</script>
</body></html>