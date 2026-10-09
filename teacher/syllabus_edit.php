<?php
require_once '../includes/config.php';
requireRole('teacher');
$sid = (int)($_GET['id'] ?? 0);
$tid = $_SESSION['user_id'];
ensureColumnExists('syllabus_topics', 'is_completed', "TINYINT(1) NOT NULL DEFAULT 0");
ensureColumnExists('syllabus_topics', 'completion_notes', "TEXT DEFAULT NULL");
ensureColumnExists('syllabus_topics', 'deletion_requested', "TINYINT(1) NOT NULL DEFAULT 0");
ensureColumnExists('syllabus_topics', 'deletion_reason', "TEXT DEFAULT NULL");
$syl = $conn->query("SELECT s.*,c.course_name,c.course_code FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.id=$sid AND s.teacher_id=$tid")->fetch_assoc();
if (!$syl) { setFlash('error','Not found.'); redirect(BASE_URL.'teacher/syllabi.php'); }

// Access is gated on admin approval: the syllabus stays visible in the teacher's
// list, but the editor itself only opens once the admin sets status to 'published'.
if ($syl['status'] !== 'published') {
    setFlash('error','This syllabus is pending admin approval ('.htmlspecialchars($syl['status']).'). You can edit it once the admin publishes it.');
    redirect(BASE_URL.'teacher/syllabi.php');
}

$pageTitle = 'Edit Syllabus: '.$syl['course_code'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_syl') {
        $desc = sanitize($_POST['course_description']);
        $out  = sanitize($_POST['course_outcomes']);
        $externalUrl = trim($_POST['external_url'] ?? '');

        // Keep the previously-uploaded image unless a new one is chosen
        $imagePath = $_POST['existing_image'] ?? null;
        $imagePath = $imagePath !== '' ? $imagePath : null;

        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedExt = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowedExt) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $destDir = '../uploads/syllabi/';
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                $newName = 'syl_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destDir . $newName)) {
                    $imagePath = $newName;
                } else {
                    setFlash('error', 'Image upload failed, but the rest of the syllabus was saved.');
                }
            } else {
                setFlash('error', 'Invalid image type. Allowed: jpg, jpeg, png, gif, webp.');
            }
        }

        $stmt=$conn->prepare("UPDATE syllabi SET course_description=?,course_outcomes=?,image_path=?,external_url=? WHERE id=? AND teacher_id=?");
        $stmt->bind_param('ssssii',$desc,$out,$imagePath,$externalUrl,$sid,$tid); $stmt->execute();
        setFlash('success','Syllabus info updated.');

    } elseif ($action === 'add_topic') {
        $wk=(int)$_POST['week_number']; $title=sanitize($_POST['topic_title'] ?? ''); 
        $tdesc=sanitize(str_replace(["\\r\\n", "\\r", "\\n", '\r\n', '\r', '\n'], "\n", $_POST['topic_description'] ?? ''));
        $lo=sanitize(str_replace(["\\r\\n", "\\r", "\\n", '\r\n', '\r', '\n'], "\n", $_POST['learning_outcomes'] ?? '')); $dm=sanitize($_POST['delivery_mode'] ?? 'blended');
        $plat=sanitize($_POST['online_platform'] ?? ''); $res=sanitize($_POST['resources'] ?? ''); $ass=sanitize($_POST['assessment_type'] ?? '');
        $stmt=$conn->prepare("INSERT INTO syllabus_topics (syllabus_id,week_number,topic_title,topic_description,learning_outcomes,delivery_mode,online_platform,resources,assessment_type) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iisssssss',$sid,$wk,$title,$tdesc,$lo,$dm,$plat,$res,$ass);
        $stmt->execute();
        setFlash('success','Topic added.');

    } elseif ($action === 'edit_topic') {
        $topicId=(int)$_POST['topic_id']; $wk=(int)$_POST['week_number']; $title=sanitize($_POST['topic_title'] ?? '');
        $tdesc=sanitize(str_replace(["\\r\\n", "\\r", "\\n", '\r\n', '\r', '\n'], "\n", $_POST['topic_description'] ?? '')); 
        $lo=sanitize(str_replace(["\\r\\n", "\\r", "\\n", '\r\n', '\r', '\n'], "\n", $_POST['learning_outcomes'] ?? '')); $dm=sanitize($_POST['delivery_mode'] ?? 'blended');
        $plat=sanitize($_POST['online_platform'] ?? ''); $res=sanitize($_POST['resources'] ?? ''); $ass=sanitize($_POST['assessment_type'] ?? '');
        $stmt=$conn->prepare("UPDATE syllabus_topics SET week_number=?,topic_title=?,topic_description=?,learning_outcomes=?,delivery_mode=?,online_platform=?,resources=?,assessment_type=? WHERE id=? AND syllabus_id=?");
        $stmt->bind_param('isssssssii',$wk,$title,$tdesc,$lo,$dm,$plat,$res,$ass,$topicId,$sid); $stmt->execute();
        setFlash('success','Topic updated.');
    } elseif ($action === 'request_delete_topic') {
        $topicId=(int)$_POST['topic_id'];
        $reason = sanitize($_POST['deletion_reason'] ?? '');
        if (empty($reason)) {
            setFlash('error', 'Please provide a reason for requesting topic deletion.');
        } else {
            $stmtReq = $conn->prepare("UPDATE syllabus_topics SET deletion_requested = 1, deletion_reason = ? WHERE id = ? AND syllabus_id = ?");
            $stmtReq->bind_param('sii', $reason, $topicId, $sid);
            $stmtReq->execute();
            if (function_exists('logActivity')) {
                logActivity($tid, "Requested deletion of topic ID {$topicId} in syllabus ID {$sid}. Reason: {$reason}", 'Syllabus');
            }
            setFlash('success', 'Topic deletion request submitted to administrator.');
        }
    } elseif ($action === 'cancel_delete_request') {
        $topicId=(int)$_POST['topic_id'];
        $stmtCancel = $conn->prepare("UPDATE syllabus_topics SET deletion_requested = 0, deletion_reason = NULL WHERE id = ? AND syllabus_id = ?");
        $stmtCancel->bind_param('ii', $topicId, $sid);
        $stmtCancel->execute();
        setFlash('success', 'Deletion request cancelled.');
    } elseif ($action === 'toggle_complete') {
        // AJAX endpoint (mirrors the student progress tracker) — no redirect, returns JSON.
        $topicId = (int)$_POST['topic_id'];
        $status  = sanitize($_POST['status'] ?? '');
        $notes   = sanitize($_POST['notes'] ?? '');
        $isCompleted = $status === 'completed' ? 1 : 0;

        if ($isCompleted === 1) {
            $checkDone = checkTopicCanBeMarkedDone($topicId, $sid);
            if (!$checkDone['can_mark_done']) {
                echo json_encode([
                    'success' => false,
                    'message' => $checkDone['message']
                ]);
                exit;
            }
        }

        $stmt = $conn->prepare("UPDATE syllabus_topics SET is_completed=?, completion_notes=? WHERE id=? AND syllabus_id=?");
        $stmt->bind_param('isii', $isCompleted, $notes, $topicId, $sid);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    } elseif ($action === 'upload_lesson') {
        $topicId=(int)$_POST['topic_id'];
        $title=sanitize($_POST['lesson_title'] ?? '');
        $desc=sanitize($_POST['lesson_description'] ?? '');
        $topic = $conn->query("SELECT id, delivery_mode FROM syllabus_topics WHERE id=$topicId AND syllabus_id=$sid")->fetch_assoc();
        if (!$topic) {
            setFlash('error','Topic not found.');
        } else {
            $pastCheck = checkPastWeeklySyllabiDone($sid, $topicId);
            if (!$pastCheck['can_proceed']) {
                setFlash('error', $pastCheck['message']);
                redirect(BASE_URL.'teacher/syllabus_edit.php?id='.$sid);
            }
            if (empty($_FILES['lesson_file']['name']) && empty($_POST['external_url'])) {
                setFlash('error','Please upload a file or add a lesson link.');
            } else {
                $uploadDir = '../uploads/materials/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $allowedExt = ['pdf','doc','docx','ppt','pptx','jpg','jpeg','png','zip'];
                $filePath = null;
                $type = 'document';
                if (!empty($_FILES['lesson_file']['name'])) {
                    $ext = strtolower(pathinfo($_FILES['lesson_file']['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExt) || $_FILES['lesson_file']['error'] !== UPLOAD_ERR_OK) {
                        setFlash('error','Invalid lesson file. Allowed: PDF, DOC/DOCX, PPT/PPTX, images, ZIP.');
                        redirect(BASE_URL.'teacher/syllabus_edit.php?id='.$sid);
                    }
                    $type = in_array($ext, ['ppt','pptx']) ? 'presentation' : 'document';
                    $filePath = uniqid('mat_') . '.' . $ext;
                    if (!move_uploaded_file($_FILES['lesson_file']['tmp_name'], $uploadDir . $filePath)) {
                        setFlash('error','Lesson file upload failed.');
                        redirect(BASE_URL.'teacher/syllabus_edit.php?id='.$sid);
                    }
                } elseif (!empty($_POST['external_url'])) {
                    $type = 'link';
                }
                $url = trim($_POST['external_url'] ?? '');
                $dm = in_array($topic['delivery_mode'], ['online','asynchronous','synchronous']) ? 'online' : ($topic['delivery_mode'] === 'face-to-face' ? 'offline' : 'both');
                $stmt=$conn->prepare("INSERT INTO learning_materials (syllabus_topic_id,syllabus_id,teacher_id,title,description,type,file_path,external_url,delivery_mode) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param('iiissssss',$topicId,$sid,$tid,$title,$desc,$type,$filePath,$url,$dm);
                $stmt->execute();
                setFlash('success','Weekly lesson file uploaded.');
            }
        }
    }
    redirect(BASE_URL.'teacher/syllabus_edit.php?id='.$sid);
}

$topics = $conn->query("SELECT * FROM syllabus_topics WHERE syllabus_id=$sid ORDER BY week_number,sort_order");
$topicsArr = []; while($t=$topics->fetch_assoc()) $topicsArr[] = $t;
$completedCount = count(array_filter($topicsArr, fn($t) => !empty($t['is_completed'])));
$materials = $conn->query("SELECT * FROM learning_materials WHERE syllabus_id=$sid AND teacher_id=$tid ORDER BY created_at DESC");
$materialsByTopic = [];
while($m=$materials->fetch_assoc()) {
    $materialsByTopic[(int)$m['syllabus_topic_id']][] = $m;
}

// Fetch enrolled students and their reading progress for this syllabus
$totalAssessmentsCount = (int)($conn->query("SELECT COUNT(*) as c FROM assessments WHERE syllabus_id = $sid")->fetch_assoc()['c'] ?? 0);

// Fetch all assessments for this syllabus indexed by topic
$assessmentsQuery = $conn->query("
    SELECT a.*,
           (SELECT COUNT(*) FROM assessment_questions aq WHERE aq.assessment_id = a.id) as q_count,
           (SELECT COUNT(*) FROM submissions s WHERE s.assessment_id = a.id) as subs_count
    FROM assessments a
    WHERE a.syllabus_id = $sid
    ORDER BY a.created_at ASC
");
$assessmentsByTopic = [];
$allAssessmentsList = [];
if ($assessmentsQuery) {
    while ($aRow = $assessmentsQuery->fetch_assoc()) {
        $allAssessmentsList[] = $aRow;
        $tId = (int)($aRow['topic_id'] ?? 0);
        if ($tId > 0) {
            $assessmentsByTopic[$tId][] = $aRow;
        }
    }
}

// Fetch individual topic progress for each student & topic
$tpAllQuery = $conn->query("
    SELECT tp.syllabus_topic_id, tp.student_id, tp.status, tp.read_percentage, tp.last_read_at, tp.completed_at
    FROM topic_progress tp
    JOIN syllabus_topics st ON tp.syllabus_topic_id = st.id
    WHERE st.syllabus_id = $sid
");
$topicProgressByTopicAndStudent = [];
if ($tpAllQuery) {
    while ($tp = $tpAllQuery->fetch_assoc()) {
        $topicProgressByTopicAndStudent[(int)$tp['syllabus_topic_id']][(int)$tp['student_id']] = $tp;
    }
}

// Fetch individual submissions for each student & assessment
$subsAllQuery = $conn->query("
    SELECT s.assessment_id, s.student_id, s.score, s.status, s.submitted_at, s.graded_at, a.topic_id, a.max_score, a.title as assessment_title
    FROM submissions s
    JOIN assessments a ON s.assessment_id = a.id
    WHERE a.syllabus_id = $sid
");
$submissionsByTopicAndStudent = [];
if ($subsAllQuery) {
    while ($sub = $subsAllQuery->fetch_assoc()) {
        $tId = (int)($sub['topic_id'] ?? 0);
        if ($tId > 0) {
            $submissionsByTopicAndStudent[$tId][(int)$sub['student_id']][] = $sub;
        }
    }
}

// Fetch enrolled students, their reading progress, and assessment submissions for this syllabus
$enrolledStudentsStmt = $conn->prepare("
    SELECT e.id as enrollment_id, e.enrolled_at, e.status as enrollment_status,
           u.id as student_id, u.full_name, u.email, u.username,
           
           -- Reading Stats (strictly based on actual reading depth)
           COALESCE(tp_stats.finished_topics, 0) as finished_topics,
           COALESCE(tp_stats.reading_topics, 0) as reading_topics,
           COALESCE(tp_stats.total_read_pct_sum, 0) as total_read_pct_sum,
           tp_stats.latest_read_at,
           
           -- Assessment Stats
           COALESCE(sub_stats.submitted_assessments, 0) as submitted_assessments,
           COALESCE(sub_stats.graded_submissions, 0) as graded_submissions,
           sub_stats.latest_submission_at
    FROM enrollments e
    JOIN users u ON e.student_id = u.id
    LEFT JOIN (
        SELECT tp.student_id,
               COUNT(DISTINCT CASE WHEN tp.status != 'not_started' AND (tp.read_percentage >= 90 OR tp.status = 'completed') THEN st.id END) as finished_topics,
               COUNT(DISTINCT CASE WHEN tp.status != 'not_started' AND (tp.read_percentage > 0 OR tp.status = 'in_progress') AND tp.read_percentage < 90 AND tp.status != 'completed' THEN st.id END) as reading_topics,
               SUM(CASE WHEN tp.status = 'not_started' THEN 0 ELSE COALESCE(tp.read_percentage, 0) END) as total_read_pct_sum,
               MAX(tp.last_read_at) as latest_read_at
        FROM syllabus_topics st
        JOIN topic_progress tp ON tp.syllabus_topic_id = st.id
        WHERE st.syllabus_id = ?
        GROUP BY tp.student_id
    ) tp_stats ON tp_stats.student_id = e.student_id
    LEFT JOIN (
        SELECT s.student_id,
               COUNT(DISTINCT s.assessment_id) as submitted_assessments,
               COUNT(DISTINCT CASE WHEN s.status = 'graded' THEN s.id END) as graded_submissions,
               MAX(s.submitted_at) as latest_submission_at
        FROM assessments a
        JOIN submissions s ON s.assessment_id = a.id
        WHERE a.syllabus_id = ?
        GROUP BY s.student_id
    ) sub_stats ON sub_stats.student_id = e.student_id
    WHERE e.syllabus_id = ? AND e.status = 'enrolled'
    ORDER BY u.full_name ASC
");
$enrolledStudentsStmt->bind_param('iii', $sid, $sid, $sid);
$enrolledStudentsStmt->execute();
$enrolledStudents = $enrolledStudentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$enrolledStudentsStmt->close();

$enrolledCount = count($enrolledStudents);
$cohortSumOverallPct = 0;
$cohortSumReadPct = 0;
$activeReadersCount = 0;
$totalTopicsCount = count($topicsArr);
$cohortTotalSubmissions = 0;

$completedCohortCount = 0;
$inProgressCohortCount = 0;
$notStartedCohortCount = 0;

foreach ($enrolledStudents as &$es) {
    $tot = $totalTopicsCount;
    $fin = (int)$es['finished_topics'];
    $read = (int)$es['reading_topics'];
    $notStarted = max(0, $tot - $fin - $read);
    
    // Accurate reading average across all syllabus topics
    $readAvgPct = $tot > 0 ? round((float)$es['total_read_pct_sum'] / $tot, 1) : 0.0;
    $readAvgPct = min(100.0, max(0.0, $readAvgPct));
    
    // Assessment deliverables
    $totAssess = $totalAssessmentsCount;
    $subAssess = (int)$es['submitted_assessments'];
    $gradedAssess = (int)$es['graded_submissions'];
    $unsubAssess = max(0, $totAssess - $subAssess);
    $assessAvgPct = $totAssess > 0 ? round(($subAssess / $totAssess) * 100, 1) : 0.0;
    $cohortTotalSubmissions += $subAssess;

    // Combined Course Overall Progress:
    if ($totAssess > 0 && $tot > 0) {
        $overallPct = round(((float)$es['total_read_pct_sum'] + ($subAssess * 100)) / ($tot + $totAssess), 1);
    } elseif ($tot > 0) {
        $overallPct = $readAvgPct;
    } else {
        $overallPct = $assessAvgPct;
    }
    $overallPct = min(100.0, max(0.0, $overallPct));
    
    // Fully completed ONLY IF 100% or both all topics read and all assessments submitted
    $isFullyCompleted = ($overallPct >= 100.0) || ($fin === $tot && ($totAssess === 0 || $subAssess === $totAssess) && $tot > 0);
    
    if ($isFullyCompleted) {
        $statusKey = 'completed';
        $statusLabel = 'Completed';
        $statusBadge = 'badge-green';
        $barColor = '#10b981';
        $completedCohortCount++;
    } elseif ($overallPct > 0 || $fin > 0 || $read > 0 || $subAssess > 0) {
        $statusKey = 'in_progress';
        $statusLabel = 'In Progress';
        $statusBadge = 'badge-blue';
        $barColor = '#3b82f6';
        $inProgressCohortCount++;
    } else {
        $statusKey = 'not_started';
        $statusLabel = 'Not Started';
        $statusBadge = 'badge-gray';
        $barColor = '#cbd5e1';
        $notStartedCohortCount++;
    }
    
    $tRead = !empty($es['latest_read_at']) ? strtotime($es['latest_read_at']) : 0;
    $tSub = !empty($es['latest_submission_at']) ? strtotime($es['latest_submission_at']) : 0;
    $maxTime = max($tRead, $tSub);
    $lastActivity = $maxTime > 0 ? date('M d, Y g:i A', $maxTime) : 'No activity yet';
    
    $es['total_topics_count'] = $tot;
    $es['finished_topics_count'] = $fin;
    $es['reading_topics_count'] = $read;
    $es['not_started_topics_count'] = $notStarted;
    $es['read_avg_pct'] = $readAvgPct;
    
    $es['total_assessments_count'] = $totAssess;
    $es['submitted_assessments_count'] = $subAssess;
    $es['graded_assessments_count'] = $gradedAssess;
    $es['unsubmitted_assessments_count'] = $unsubAssess;
    $es['assess_avg_pct'] = $assessAvgPct;
    
    $es['overall_pct'] = $overallPct;
    $es['status_key'] = $statusKey;
    $es['status_label'] = $statusLabel;
    $es['status_badge'] = $statusBadge;
    $es['bar_color'] = $barColor;
    $es['last_activity_formatted'] = $lastActivity;
    
    $cohortSumOverallPct += $overallPct;
    $cohortSumReadPct += $readAvgPct;
    if ($readAvgPct > 0 || $fin > 0 || $read > 0) $activeReadersCount++;
}
unset($es);

$cohortAvgOverallPct = $enrolledCount > 0 ? round($cohortSumOverallPct / $enrolledCount, 1) : 0;
$cohortAvgReadPct = $enrolledCount > 0 ? round($cohortSumReadPct / $enrolledCount, 1) : 0;
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="breadcrumb"><a href="syllabi.php">Syllabi</a><span>›</span> <?= htmlspecialchars($syl['course_code']) ?></div>

<div class="page-header">
    <div class="page-header-left">
        <h2><?= htmlspecialchars($syl['course_code']) ?>: <?= htmlspecialchars($syl['course_name']) ?></h2>
        <p>Edit syllabus details, weekly topics, and lesson files</p>
    </div>
    <div style="display:flex;gap:8px">
        <button class="btn btn-primary" onclick="openModal('addTopicModal')"><i class="fas fa-plus"></i> Add Topic</button>
    </div>
</div>

<div class="tab-nav">
    <button class="tab-btn active" onclick="showTab('mapping',this)">Topic Mapping (<?= count($topicsArr) ?> topics<?= count($topicsArr) ? ', '.$completedCount.'/'.count($topicsArr).' taught' : '' ?>)</button>
    <button class="tab-btn" onclick="showTab('info',this)">Syllabus Info</button>
    <button class="tab-btn" onclick="showTab('students',this)"><i class="fas fa-user-graduate"></i> Enrolled Students (<?= $enrolledCount ?>)</button>
</div>

<div class="tab-pane active" id="mapping">
<?php if (empty($topicsArr)): ?>
<div class="empty-state card"><div class="card-body">
    <i class="fas fa-map"></i>
    <h3>No topics yet</h3>
    <p>Start mapping your syllabus by adding weekly topics</p>
    <button class="btn btn-primary" onclick="openModal('addTopicModal')" style="margin-top:16px"><i class="fas fa-plus"></i> Add First Topic</button>
</div></div>
<?php else: ?>

<!-- Teacher Delivery Progress Banner -->
<?php 
    $teachingPct = count($topicsArr) > 0 ? round($completedCount / count($topicsArr) * 100) : 0;
?>
<div class="card" style="margin-bottom:16px;background:var(--bg);border:1px solid var(--border)">
    <div class="card-body" style="padding:12px 18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="width:38px;height:38px;border-radius:8px;background:<?= $teachingPct >= 100 ? '#10b981' : 'var(--primary)' ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:16px">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div>
                <div style="font-weight:700;font-size:14px">Teacher Course Delivery Progress</div>
                <div style="font-size:12px;color:var(--text3)"><?= $completedCount ?> of <?= count($topicsArr) ?> weekly topics marked as taught & delivered in class</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:12px">
            <div style="width:140px;height:8px;background:var(--border);border-radius:99px;overflow:hidden">
                <div style="width:<?= $teachingPct ?>%;height:100%;background:<?= $teachingPct >= 100 ? '#10b981' : 'var(--primary)' ?>;border-radius:99px"></div>
            </div>
            <strong style="font-size:13px;color:<?= $teachingPct >= 100 ? '#10b981' : 'var(--primary)' ?>"><?= $teachingPct ?>% Taught</strong>
        </div>
    </div>
</div>

<!-- Mode legend -->
<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
    <span class="mode-pill mode-face"><i class="fas fa-users"></i> Face-to-Face</span>
    <span class="mode-pill mode-online"><i class="fas fa-laptop"></i> Online</span>
    <span class="mode-pill mode-blended"><i class="fas fa-layer-group"></i> Blended</span>
    <span class="mode-pill mode-async"><i class="fas fa-clock"></i> Asynchronous</span>
    <span class="mode-pill mode-sync"><i class="fas fa-video"></i> Synchronous</span>
</div>

<div class="week-timeline">
<?php foreach($topicsArr as $t):
$tId = (int)$t['id'];
$modeClass = ['face-to-face'=>'face','online'=>'online','blended'=>'blended','asynchronous'=>'async','synchronous'=>'sync'][$t['delivery_mode']] ?? 'blended';
$topicMaterials = $materialsByTopic[$tId] ?? [];
$topicAssessments = $assessmentsByTopic[$tId] ?? [];

// Calculate student completion specifically for this topic/week
$completedStudentsThisTopic = [];
$pendingStudentsThisTopic = [];

foreach ($enrolledStudents as $stu) {
    $sId = (int)$stu['student_id'];
    $tp = $topicProgressByTopicAndStudent[$tId][$sId] ?? null;
    $readPct = $tp ? (float)$tp['read_percentage'] : 0.0;
    $readStatus = $tp ? $tp['status'] : 'not_started';
    $isReadDone = ($tp && ($readStatus === 'completed' || $readPct >= 90.0));
    
    $topicSubs = $submissionsByTopicAndStudent[$tId][$sId] ?? [];
    
    $studentTopicData = [
        'id' => $sId,
        'name' => $stu['full_name'],
        'email' => $stu['email'],
        'read_pct' => $readPct,
        'read_status' => $readStatus,
        'last_read' => $tp ? $tp['last_read_at'] : null,
        'completed_at' => $tp ? $tp['completed_at'] : null,
        'is_read_done' => $isReadDone,
        'submissions' => $topicSubs
    ];
    
    if ($isReadDone) {
        $completedStudentsThisTopic[] = $studentTopicData;
    } else {
        $pendingStudentsThisTopic[] = $studentTopicData;
    }
}

$topicDoneCount = count($completedStudentsThisTopic);
$topicPendingCount = count($pendingStudentsThisTopic);
$topicDonePct = $enrolledCount > 0 ? round(($topicDoneCount / $enrolledCount) * 100) : 0;
$isDone = !empty($t['is_completed']);
$pastCheck = checkPastWeeklySyllabiDone($sid, $t['id'], (int)$t['week_number']);
?>
<div class="week-item" id="topic-<?= $t['id'] ?>">
    <div class="week-dot <?= $isDone ? 'completed' : $modeClass ?>"></div>
    <div class="week-card <?= $modeClass ?>" style="<?= $isDone ? 'border-color:rgba(16,185,129,0.35);' : '' ?>">
        <!-- Header -->
        <div class="week-header" style="cursor:pointer" onclick="toggleWeekDetails(<?= $t['id'] ?>)" title="Click to expand/collapse full week details">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span class="week-num">Week <?= $t['week_number'] ?></span>
                <span class="mode-pill mode-<?= $modeClass ?>"><?= ucfirst($t['delivery_mode']) ?></span>
                <?php if (!$pastCheck['can_proceed']): ?>
                    <span class="badge" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;font-size:11px;font-weight:700" title="<?= htmlspecialchars($pastCheck['message']) ?>">
                        <i class="fas fa-lock"></i> Prior Weeks Incomplete
                    </span>
                <?php endif; ?>
                <span class="badge badge-gray" style="font-size:11px;font-weight:600">
                    <i class="fas fa-folder-open" style="margin-right:3px"></i> <?= count($topicMaterials) ?> File<?= count($topicMaterials) != 1 ? 's' : '' ?>
                </span>
                <span class="badge <?= !empty($topicAssessments) ? 'badge-blue' : 'badge-gray' ?>" style="font-size:11px;font-weight:600">
                    <i class="fas fa-clipboard-check" style="margin-right:3px"></i> <?= count($topicAssessments) ?> Assessment<?= count($topicAssessments) != 1 ? 's' : '' ?>
                </span>
                <?php if($isDone): ?>
                    <span class="badge badge-green" style="font-size:11px;font-weight:700">
                        <i class="fas fa-check-circle"></i> Taught
                    </span>
                <?php endif; ?>
            </div>
            <div style="display:flex;align-items:center;gap:6px" onclick="event.stopPropagation()">
                <?php 
                    $checkThisTopicState = checkTopicCanBeMarkedDone($t['id'], $sid);
                    $canMarkDoneThisTopic = $checkThisTopicState['can_mark_done'];
                    $cannotDoneMsgThisTopic = $checkThisTopicState['message'];
                ?>
                <?php if($isDone): ?>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); updateStatus(<?= $t['id'] ?>, 'not_started')" title="Undo completed teaching status">
                        <i class="fas fa-undo"></i> Undo
                    </button>
                <?php elseif (!$canMarkDoneThisTopic): ?>
                    <button type="button" class="btn btn-sm" 
                        style="font-size:11px;font-weight:600;padding:4px 9px;background:#f8fafc;border:1px solid #cbd5e1;color:#64748b;border-radius:6px;cursor:pointer"
                        onclick="event.stopPropagation(); showCannotMarkDoneAlert('<?= htmlspecialchars(addslashes($cannotDoneMsgThisTopic), ENT_QUOTES) ?>')"
                        title="<?= htmlspecialchars($cannotDoneMsgThisTopic) ?>">
                        <i class="fas fa-lock" style="font-size:10px;color:#94a3b8"></i> Mark Done
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-success btn-sm" 
                        data-topic-id="<?= $t['id'] ?>"
                        data-topic-title="<?= htmlspecialchars('Week '.$t['week_number'].' - '.$t['topic_title'], ENT_QUOTES) ?>"
                        data-topic-notes="<?= htmlspecialchars($t['completion_notes'] ?? '', ENT_QUOTES) ?>"
                        onclick="event.stopPropagation(); handleDoneClick(this)"
                        title="Mark this weekly topic as taught / completed">
                        <i class="fas fa-check"></i> Mark Done
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary btn-sm" onclick='editTopic(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)' title="Edit Topic"><i class="fas fa-edit"></i></button>
                <?php if (!empty($t['deletion_requested'])): ?>
                    <span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-size:11px;padding:3px 8px" title="Deletion requested from Admin">
                        <i class="fas fa-clock"></i> Deletion Requested
                    </span>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Cancel this deletion request?')">
                        <input type="hidden" name="action" value="cancel_delete_request">
                        <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                        <button class="btn btn-secondary btn-sm" style="padding:4px 8px;font-size:11px" title="Cancel Deletion Request">
                            <i class="fas fa-undo"></i> Cancel Request
                        </button>
                    </form>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding:4px 8px;font-size:11px;color:#dc2626"
                        data-topic-id="<?= $t['id'] ?>"
                        data-topic-title="<?= htmlspecialchars('Week '.$t['week_number'].' - '.$t['topic_title'], ENT_QUOTES) ?>"
                        onclick="event.stopPropagation(); openDeleteRequestModal(this)"
                        title="Request Deletion from Administrator">
                        <i class="fas fa-trash-alt"></i> Request Delete
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($t['deletion_requested'])): ?>
        <div style="margin-top:8px;padding:8px 12px;background:#fffbeb;border:1px solid #fef3c7;border-radius:6px;font-size:12px;color:#92400e;display:flex;align-items:center;gap:8px">
            <i class="fas fa-exclamation-triangle" style="color:#d97706"></i>
            <div>
                <strong>Deletion Request Pending:</strong> <?= htmlspecialchars($t['deletion_reason'] ?? 'Awaiting administrative review') ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Topic Title & Description -->
        <div class="week-title" style="cursor:pointer;margin-top:6px" onclick="toggleWeekDetails(<?= $t['id'] ?>)">
            <?= htmlspecialchars($t['topic_title']) ?>
        </div>
        <?php if($t['topic_description']): ?>
            <p style="font-size:13px;color:var(--text3);margin-top:6px;line-height:1.5"><?= nl2br(htmlspecialchars($t['topic_description'])) ?></p>
        <?php endif; ?>

        <!-- Teacher's Teaching Delivery Notes (if taught) -->
        <?php if($isDone && !empty($t['completion_notes'])): ?>
        <div style="margin-top:10px;padding:10px 14px;background:#f0fdf4;border-radius:8px;border-left:4px solid #10b981">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <strong style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#166534">
                    <i class="fas fa-chalkboard-teacher"></i> Teacher's Delivery Notes
                </strong>
                <button type="button" class="btn btn-secondary btn-sm" style="padding:1px 6px;font-size:10px"
                    data-topic-id="<?= $t['id'] ?>"
                    data-topic-title="<?= htmlspecialchars('Week '.$t['week_number'].' - '.$t['topic_title'], ENT_QUOTES) ?>"
                    data-topic-notes="<?= htmlspecialchars($t['completion_notes'] ?? '', ENT_QUOTES) ?>"
                    onclick="event.stopPropagation(); handleDoneClick(this)">
                    <i class="fas fa-pen"></i> Edit Notes
                </button>
            </div>
            <p style="font-size:13px;margin:4px 0 0;color:#14532d;line-height:1.4"><?= nl2br(htmlspecialchars($t['completion_notes'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Aligned Assessments with Clickable Links (Not just a label!) -->
        <?php if (!empty($topicAssessments)): ?>
        <div style="margin-top:12px;padding:10px 14px;background:var(--bg);border-radius:8px;border:1px solid var(--border)">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                <strong style="font-size:11px;text-transform:uppercase;color:var(--text2);display:flex;align-items:center;gap:5px">
                    <i class="fas fa-tasks" style="color:var(--primary)"></i> Week <?= $t['week_number'] ?> Assessments & Quizzes
                </strong>
                <?php if (!$pastCheck['can_proceed']): ?>
                    <button type="button" class="btn btn-secondary btn-sm" style="font-size:10px;padding:2px 8px;opacity:0.75;background:#fef2f2;border-color:#fca5a5;color:#991b1b" onclick="event.stopPropagation(); showWeeklyLockAlert('<?= htmlspecialchars(addslashes($pastCheck['message']), ENT_QUOTES) ?>')" title="<?= htmlspecialchars($pastCheck['message']) ?>">
                        <i class="fas fa-lock"></i> Add Assessment
                    </button>
                <?php else: ?>
                    <a href="assessments.php?syllabus_id=<?= $sid ?>&topic_id=<?= $t['id'] ?>" class="btn btn-secondary btn-sm" style="font-size:10px;padding:2px 8px">
                        <i class="fas fa-plus"></i> Add Assessment
                    </a>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <?php foreach ($topicAssessments as $ass): 
                    $tc = ['quiz'=>'badge-green','assignment'=>'badge-blue','exam'=>'badge-red','activity'=>'badge-purple','project'=>'badge-orange'][$ass['type']] ?? 'badge-blue';
                ?>
                <div style="background:#fff;border:1px solid var(--border);border-radius:8px;padding:8px 12px;display:flex;align-items:center;gap:10px;box-shadow:0 1px 3px rgba(0,0,0,0.03);flex-wrap:wrap">
                    <span class="badge <?= $tc ?>" style="text-transform:capitalize;font-size:10px;font-weight:700"><?= $ass['type'] ?></span>
                    <a href="assessment_questions.php?id=<?= $ass['id'] ?>" style="font-size:12px;font-weight:700;color:var(--primary);text-decoration:none;display:inline-flex;align-items:center;gap:5px" title="Click to view & edit questions">
                        <i class="fas fa-edit"></i> <?= htmlspecialchars($ass['title']) ?>
                        <span style="font-size:11px;color:var(--text3);font-weight:500">(<?= number_format($ass['max_score'], 1) ?> pts &bull; <?= $ass['q_count'] ?> Qs)</span>
                    </a>
                    <a href="grades.php?assessment=<?= $ass['id'] ?>" class="badge badge-blue" style="font-size:11px;padding:3px 8px;display:inline-flex;align-items:center;gap:4px;text-decoration:none" title="Review student submissions & grades">
                        <i class="fas fa-user-check"></i> <?= $ass['subs_count'] ?> submissions
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif (!empty($t['assessment_type'])): ?>
        <div style="margin-top:12px;padding:10px 14px;background:var(--bg);border-radius:8px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
            <span style="font-size:12px;color:var(--text2)">
                <i class="fas fa-tasks" style="color:var(--text3);margin-right:5px"></i> Planned Assessment: <strong><?= htmlspecialchars($t['assessment_type']) ?></strong>
            </span>
            <?php if (!$pastCheck['can_proceed']): ?>
                <button type="button" class="btn btn-secondary btn-sm" style="font-size:11px;padding:3px 9px;opacity:0.75;background:#fef2f2;border-color:#fca5a5;color:#991b1b" onclick="event.stopPropagation(); showWeeklyLockAlert('<?= htmlspecialchars(addslashes($pastCheck['message']), ENT_QUOTES) ?>')" title="<?= htmlspecialchars($pastCheck['message']) ?>">
                    <i class="fas fa-lock"></i> Create Assessment
                </button>
            <?php else: ?>
                <a href="assessments.php?syllabus_id=<?= $sid ?>&topic_id=<?= $t['id'] ?>" class="btn btn-secondary btn-sm" style="font-size:11px;padding:3px 9px">
                    <i class="fas fa-plus"></i> Create Assessment
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Lesson Materials Quick Row -->
        <?php if(!empty($topicMaterials)): ?>
        <div style="margin-top:10px;padding:10px 14px;background:var(--bg);border-radius:8px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                <strong style="font-size:11px;text-transform:uppercase;color:var(--text2);display:flex;align-items:center;gap:5px">
                    <i class="fas fa-folder-open" style="color:var(--success)"></i> Learning Materials (<?= count($topicMaterials) ?>)
                </strong>
                <?php if (!$pastCheck['can_proceed']): ?>
                    <button type="button" class="btn btn-secondary btn-sm" style="font-size:10px;padding:2px 8px;opacity:0.75;background:#fef2f2;border-color:#fca5a5;color:#991b1b" onclick="event.stopPropagation(); showWeeklyLockAlert('<?= htmlspecialchars(addslashes($pastCheck['message']), ENT_QUOTES) ?>')" title="<?= htmlspecialchars($pastCheck['message']) ?>">
                        <i class="fas fa-lock"></i> Add Material
                    </button>
                <?php else: ?>
                    <a href="materials.php?syl=<?= $sid ?>&topic_id=<?= $t['id'] ?>&add=1" class="btn btn-secondary btn-sm" style="font-size:10px;padding:2px 8px">
                        <i class="fas fa-plus"></i> Add Material
                    </a>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <?php foreach($topicMaterials as $m):
                    $mType = $m['type'] ?? 'module';
                    $readerUrl = BASE_URL . 'student/read_material.php?id=' . $m['id'];
                    $icon = 'fa-book-reader';
                    $prefix = 'Read';
                    $btnClass = 'btn-primary';
                    if ($mType === 'video') {
                        $icon = 'fa-play-circle';
                        $prefix = 'Watch';
                    } elseif ($mType === 'document') {
                        $ext = strtolower(pathinfo($m['file_path'] ?? '', PATHINFO_EXTENSION));
                        $icon = ($ext === 'pdf') ? 'fa-file-pdf' : (($ext === 'docx' || $ext === 'doc') ? 'fa-file-word' : 'fa-file-alt');
                        $prefix = 'Study';
                    } elseif ($mType === 'presentation') {
                        $icon = 'fa-file-powerpoint';
                        $prefix = 'Slides';
                    } elseif ($mType === 'link') {
                        $icon = 'fa-external-link-alt';
                        $prefix = 'Resource';
                    }
                ?>
                <a class="btn <?= $btnClass ?> btn-sm" href="<?= htmlspecialchars($readerUrl) ?>" target="_blank" title="Preview Material in Reader" style="display:inline-flex;align-items:center;gap:6px">
                    <i class="fas <?= $icon ?>"></i>
                    <span><?= htmlspecialchars($m['title']) ?></span>
                    <?php if (!empty($m['file_path'])): ?>
                        <small style="opacity:0.85"><i class="fas fa-paperclip"></i></small>
                    <?php endif; ?>
                    <?php if ($mType === 'module' && !empty($m['estimated_read_time'])): ?>
                        <span style="font-size:10px;opacity:0.85">(<?= (int)$m['estimated_read_time'] ?>m)</span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div style="margin-top:10px;padding:8px 14px;background:var(--bg);border-radius:8px;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:12px;color:var(--text3)">
                <i class="fas fa-folder-open" style="margin-right:5px"></i> No learning materials attached yet.
            </span>
            <?php if (!$pastCheck['can_proceed']): ?>
                <button type="button" class="btn btn-secondary btn-sm" style="font-size:10px;padding:2px 8px;opacity:0.75;background:#fef2f2;border-color:#fca5a5;color:#991b1b" onclick="event.stopPropagation(); showWeeklyLockAlert('<?= htmlspecialchars(addslashes($pastCheck['message']), ENT_QUOTES) ?>')" title="<?= htmlspecialchars($pastCheck['message']) ?>">
                    <i class="fas fa-lock"></i> Add Material
                </button>
            <?php else: ?>
                <a href="materials.php?syl=<?= $sid ?>&topic_id=<?= $t['id'] ?>&add=1" class="btn btn-secondary btn-sm" style="font-size:10px;padding:2px 8px">
                    <i class="fas fa-plus"></i> Add Material
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Student Completion Gauge Bar for Week -->
        <div style="margin-top:12px;padding:10px 14px;background:var(--bg);border-radius:8px">
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;margin-bottom:6px">
                <span style="font-weight:600;color:var(--text2)">
                    <i class="fas fa-user-graduate" style="color:<?= $topicDonePct >= 80 ? 'var(--success)' : ($topicDonePct >= 40 ? 'var(--primary)' : 'var(--warning)') ?>;margin-right:4px"></i> Week <?= $t['week_number'] ?> Student Completion
                </span>
                <strong style="color:<?= $topicDonePct >= 80 ? 'var(--success)' : ($topicDonePct >= 40 ? 'var(--primary)' : 'var(--warning)') ?>">
                    <?= $topicDoneCount ?> / <?= $enrolledCount ?> Completed (<?= $topicDonePct ?>%)
                </strong>
            </div>
            <div style="height:7px;background:#e2e8f0;border-radius:99px;overflow:hidden">
                <div style="width:<?= $topicDonePct ?>%;height:100%;background:<?= $topicDonePct >= 80 ? '#10b981' : ($topicDonePct >= 40 ? '#3b82f6' : '#f59e0b') ?>;border-radius:99px;transition:width 0.3s ease"></div>
            </div>
        </div>

        <!-- Per Week Expand / Collapse Button -->
        <div style="margin-top:12px">
            <button type="button" class="btn btn-secondary btn-sm week-expand-trigger" id="btn-toggle-<?= $t['id'] ?>" onclick="toggleWeekDetails(<?= $t['id'] ?>)" style="width:100%;justify-content:center;display:flex;align-items:center;gap:8px;font-weight:600;padding:8px">
                <i class="fas fa-chevron-down" id="chevron-<?= $t['id'] ?>"></i>
                <span id="label-<?= $t['id'] ?>">View Week <?= $t['week_number'] ?> Full Details & Student Roster (<?= $topicDoneCount ?> Done, <?= $topicPendingCount ?> Pending)</span>
            </button>
        </div>

        <!-- Full Week Details Drawer (Assessments, Materials, Students who complete and not) -->
        <div class="week-full-details" id="week-details-<?= $t['id'] ?>" style="display:none;margin-top:14px;border-top:2px dashed var(--border);padding-top:16px">
            <!-- Learning Outcomes & ILO section -->
            <?php if($t['learning_outcomes']): ?>
            <div style="margin-bottom:14px;padding:12px 16px;background:var(--bg);border-radius:8px">
                <strong style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text2)">
                    <i class="fas fa-bullseye" style="color:var(--primary);margin-right:4px"></i> Intended Learning Outcomes (ILOs)
                </strong>
                <p style="font-size:13px;margin-top:6px;line-height:1.5;color:var(--text)"><?= formatMultilineText($t['learning_outcomes']) ?></p>
            </div>
            <?php endif; ?>

            <!-- Week Curriculum Platform & Meta -->
            <div style="display:flex;gap:12px;margin-bottom:14px;flex-wrap:wrap">
                <?php if($t['online_platform']): ?>
                    <span style="font-size:12px;background:var(--bg);padding:6px 12px;border-radius:6px;border:1px solid var(--border)">
                        <i class="fas fa-laptop" style="color:var(--primary);margin-right:4px"></i> Platform: <strong><?= htmlspecialchars($t['online_platform']) ?></strong>
                    </span>
                <?php endif; ?>
                <?php if($t['resources']): ?>
                    <span style="font-size:12px;background:var(--bg);padding:6px 12px;border-radius:6px;border:1px solid var(--border)">
                        <i class="fas fa-book" style="color:var(--success);margin-right:4px"></i> Resources: <?= htmlspecialchars($t['resources']) ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Student Completion Breakdown for Week -->
            <div style="background:#fff;border:1px solid var(--border);border-radius:10px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,0.02)">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:12px">
                    <div>
                        <h4 style="font-size:14px;font-weight:700;margin:0;display:flex;align-items:center;gap:6px">
                            <i class="fas fa-users" style="color:var(--primary)"></i> Week <?= $t['week_number'] ?> Student Completion Roster
                        </h4>
                        <div style="font-size:12px;color:var(--text3);margin-top:2px">
                            Review students who completed reading modules & submissions vs. those pending.
                        </div>
                    </div>
                    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                        <button type="button" class="btn btn-secondary btn-sm week-filter-btn active" onclick="filterWeekStudents(<?= $t['id'] ?>, 'all', this)" style="font-size:11px;padding:3px 8px">All (<?= $enrolledCount ?>)</button>
                        <button type="button" class="btn btn-secondary btn-sm week-filter-btn" onclick="filterWeekStudents(<?= $t['id'] ?>, 'completed', this)" style="font-size:11px;padding:3px 8px">
                            <i class="fas fa-check-circle" style="color:var(--success);margin-right:2px"></i> Completed (<?= $topicDoneCount ?>)
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm week-filter-btn" onclick="filterWeekStudents(<?= $t['id'] ?>, 'pending', this)" style="font-size:11px;padding:3px 8px">
                            <i class="fas fa-clock" style="color:var(--warning);margin-right:2px"></i> Pending (<?= $topicPendingCount ?>)
                        </button>
                        <input type="text" class="form-control week-search-input" placeholder="Search student..." style="width:160px;font-size:11px;height:28px;padding:2px 8px" onkeyup="searchWeekStudentList(<?= $t['id'] ?>, this.value)">
                    </div>
                </div>

                <div class="week-students-tables" id="week-students-tables-<?= $t['id'] ?>">
                    <!-- Completed Students Table -->
                    <div class="week-section-completed" id="week-completed-sec-<?= $t['id'] ?>" style="margin-bottom:16px">
                        <div style="font-size:12px;font-weight:700;color:#065f46;background:#ecfdf5;border:1px solid #a7f3d0;padding:6px 12px;border-radius:6px;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                            <i class="fas fa-check-double"></i> Completed Students (<?= $topicDoneCount ?>) &bull; <?= $topicDonePct ?>% of class
                        </div>
                        <?php if (empty($completedStudentsThisTopic)): ?>
                            <div style="padding:16px;text-align:center;color:var(--text3);font-size:12px;border:1px dashed var(--border);border-radius:6px">
                                No students have completed reading modules for Week <?= $t['week_number'] ?> yet.
                            </div>
                        <?php else: ?>
                            <div class="table-wrap" style="max-height:280px;overflow-y:auto;border:1px solid var(--border);border-radius:6px">
                                <table style="font-size:12px;margin:0">
                                    <thead>
                                        <tr style="background:#f8fafc">
                                            <th>Student</th>
                                            <th>Reading Material Progress</th>
                                            <th>Assessment Status</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($completedStudentsThisTopic as $cs): 
                                            $cInitials = strtoupper(substr($cs['name'], 0, 2));
                                            $cSub = !empty($cs['submissions']) ? $cs['submissions'][0] : null;
                                        ?>
                                        <tr class="week-stu-row" data-name="<?= htmlspecialchars(strtolower($cs['name'])) ?>" data-email="<?= htmlspecialchars(strtolower($cs['email'])) ?>">
                                            <td>
                                                <div style="display:flex;align-items:center;gap:8px">
                                                    <div class="avatar-sm" style="width:28px;height:28px;font-size:11px;background:#ecfdf5;color:#065f46"><?= $cInitials ?></div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($cs['name']) ?></strong>
                                                        <div style="font-size:11px;color:var(--text3)"><?= htmlspecialchars($cs['email']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-green" style="font-size:10px;padding:2px 6px">
                                                    <i class="fas fa-check"></i> <?= (int)$cs['read_pct'] ?>% Read
                                                </span>
                                                <?php if($cs['completed_at']): ?>
                                                    <div style="font-size:10px;color:var(--text3);margin-top:2px"><?= date('M d, Y g:i A', strtotime($cs['completed_at'])) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($topicAssessments)): ?>
                                                    <?php if ($cSub): ?>
                                                        <span class="badge <?= $cSub['status']==='graded' ? 'badge-blue' : 'badge-orange' ?>" style="font-size:10px;padding:2px 6px">
                                                            <?= $cSub['status']==='graded' ? number_format($cSub['score'], 1) . ' pts' : 'Submitted' ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-gray" style="font-size:10px;padding:2px 6px">Not submitted</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span style="color:var(--text3);font-size:11px">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge badge-green" style="font-size:10px">Completed</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pending / In Progress Students Table -->
                    <div class="week-section-pending" id="week-pending-sec-<?= $t['id'] ?>">
                        <div style="font-size:12px;font-weight:700;color:#92400e;background:#fef3c7;border:1px solid #fde68a;padding:6px 12px;border-radius:6px;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                            <i class="fas fa-hourglass-half"></i> In Progress / Not Started Students (<?= $topicPendingCount ?>) &bull; <?= 100 - $topicDonePct ?>% of class
                        </div>
                        <?php if (empty($pendingStudentsThisTopic)): ?>
                            <div style="padding:16px;text-align:center;color:var(--success);font-size:12px;border:1px dashed #a7f3d0;border-radius:6px;background:#f0fdf4">
                                <i class="fas fa-check-circle" style="margin-right:4px"></i> 100% of enrolled students have completed this week!
                            </div>
                        <?php else: ?>
                            <div class="table-wrap" style="max-height:280px;overflow-y:auto;border:1px solid var(--border);border-radius:6px">
                                <table style="font-size:12px;margin:0">
                                    <thead>
                                        <tr style="background:#f8fafc">
                                            <th>Student</th>
                                            <th>Reading Material Progress</th>
                                            <th>Assessment Status</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingStudentsThisTopic as $ps): 
                                            $pInitials = strtoupper(substr($ps['name'], 0, 2));
                                            $pSub = !empty($ps['submissions']) ? $ps['submissions'][0] : null;
                                        ?>
                                        <tr class="week-stu-row" data-name="<?= htmlspecialchars(strtolower($ps['name'])) ?>" data-email="<?= htmlspecialchars(strtolower($ps['email'])) ?>">
                                            <td>
                                                <div style="display:flex;align-items:center;gap:8px">
                                                    <div class="avatar-sm" style="width:28px;height:28px;font-size:11px;background:#fef3c7;color:#92400e"><?= $pInitials ?></div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($ps['name']) ?></strong>
                                                        <div style="font-size:11px;color:var(--text3)"><?= htmlspecialchars($ps['email']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if($ps['read_pct'] > 0): ?>
                                                    <span class="badge badge-blue" style="font-size:10px;padding:2px 6px">
                                                        <i class="fas fa-book-open"></i> <?= (int)$ps['read_pct'] ?>% Reading
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-gray" style="font-size:10px;padding:2px 6px">0% Not Started</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($topicAssessments)): ?>
                                                    <?php if ($pSub): ?>
                                                        <span class="badge <?= $pSub['status']==='graded' ? 'badge-blue' : 'badge-orange' ?>" style="font-size:10px;padding:2px 6px">
                                                            <?= $pSub['status']==='graded' ? number_format($pSub['score'], 1) . ' pts' : 'Submitted' ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-gray" style="font-size:10px;padding:2px 6px">Pending</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span style="color:var(--text3);font-size:11px">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $ps['read_pct'] > 0 ? 'badge-blue' : 'badge-gray' ?>" style="font-size:10px">
                                                    <?= $ps['read_pct'] > 0 ? 'In Progress' : 'Not Started' ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<div class="tab-pane" id="info">
<div class="card"><div class="card-body">
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="update_syl">
    <input type="hidden" name="existing_image" value="<?= htmlspecialchars($syl['image_path'] ?? '') ?>">
    <div class="form-group"><label>Course Description</label>
    <textarea name="course_description" class="form-control" rows="4"><?= htmlspecialchars($syl['course_description']) ?></textarea></div>
    <div class="form-group"><label>Course Learning Outcomes</label>
    <textarea name="course_outcomes" class="form-control" rows="5" placeholder="1. Identify...\n2. Apply...\n3. Analyze..."><?= htmlspecialchars($syl['course_outcomes']) ?></textarea></div>

    <div class="form-group">
        <label>Cover Image <span style="color:var(--text3);font-weight:400">(optional — shown to your students)</span></label>
        <?php if (!empty($syl['image_path'])): ?>
        <div style="margin-bottom:8px">
            <img src="<?= BASE_URL ?>uploads/syllabi/<?= htmlspecialchars($syl['image_path']) ?>" style="max-height:100px;border-radius:8px;border:1px solid var(--border)">
        </div>
        <?php endif; ?>
        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
    </div>

    <div class="form-group">
        <label>External URL <span style="color:var(--text3);font-weight:400">(optional — e.g. Google Classroom, Moodle link)</span></label>
        <input type="url" name="external_url" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($syl['external_url'] ?? '') ?>">
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
</form>
</div></div>
</div>

<div class="tab-pane" id="students">
    <!-- Summary Header Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:14px;margin-bottom:20px">
        <div class="card"><div class="card-body" style="padding:16px 20px;text-align:center">
            <div style="font-size:26px;font-weight:800;color:var(--text);line-height:1.2"><?= $enrolledCount ?></div>
            <div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase;margin-top:4px">Enrolled Students</div>
        </div></div>

        <div class="card"><div class="card-body" style="padding:16px 20px;text-align:center">
            <div style="font-size:26px;font-weight:800;color:#2563eb;line-height:1.2"><?= $cohortAvgOverallPct ?>%</div>
            <div style="font-size:11px;color:#2563eb;font-weight:600;text-transform:uppercase;margin-top:4px">Cohort Avg Progress</div>
        </div></div>

        <div class="card"><div class="card-body" style="padding:16px 20px;text-align:center">
            <div style="font-size:26px;font-weight:800;color:#059669;line-height:1.2"><?= $activeReadersCount ?> / <?= $enrolledCount ?></div>
            <div style="font-size:11px;color:#059669;font-weight:600;text-transform:uppercase;margin-top:4px">Active Learners</div>
        </div></div>

        <div class="card"><div class="card-body" style="padding:16px 20px;text-align:center">
            <div style="font-size:26px;font-weight:800;color:#7c3aed;line-height:1.2"><?= count($topicsArr) ?> Topics <?php if($totalAssessmentsCount > 0): ?><span style="font-size:14px;color:var(--text3);font-weight:600">· <?= $totalAssessmentsCount ?> Assessments</span><?php endif; ?></div>
            <div style="font-size:11px;color:#7c3aed;font-weight:600;text-transform:uppercase;margin-top:4px">Course Scope</div>
        </div></div>
    </div>

    <?php if (empty($enrolledStudents)): ?>
    <div class="empty-state card"><div class="card-body">
        <i class="fas fa-user-graduate"></i>
        <h3>No students enrolled yet</h3>
        <p>Students enrolled in this section will appear here with real-time reading and learning progress.</p>
    </div></div>
    <?php else: ?>
    <div class="card">
        <div class="card-body" style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
            <div>
                <strong style="font-size:14px">Student Learning Progress Breakdown</strong>
                <p style="font-size:12px;color:var(--text3);margin:2px 0 0">Tracking reading module completion, assessment submissions, and combined course engagement</p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <div class="student-status-filters" style="display:flex;gap:4px">
                    <button type="button" class="btn btn-secondary btn-sm student-filter-btn active" style="font-size:11px;padding:3px 9px" onclick="filterByStudentStatus('all', this)">All (<?= $enrolledCount ?>)</button>
                    <button type="button" class="btn btn-secondary btn-sm student-filter-btn" style="font-size:11px;padding:3px 9px" onclick="filterByStudentStatus('completed', this)">Completed (<?= $completedCohortCount ?>)</button>
                    <button type="button" class="btn btn-secondary btn-sm student-filter-btn" style="font-size:11px;padding:3px 9px" onclick="filterByStudentStatus('in_progress', this)">In Progress (<?= $inProgressCohortCount ?>)</button>
                    <?php if ($notStartedCohortCount > 0): ?>
                    <button type="button" class="btn btn-secondary btn-sm student-filter-btn" style="font-size:11px;padding:3px 9px" onclick="filterByStudentStatus('not_started', this)">Not Started (<?= $notStartedCohortCount ?>)</button>
                    <?php endif; ?>
                </div>
                <input type="text" id="studentSearchInput" class="form-control" placeholder="Search student name or email..." style="max-width:240px;font-size:12px;height:34px" onkeyup="filterEnrolledStudents()">
            </div>
        </div>
        <div class="table-wrap">
            <table id="enrolledStudentsTable">
                <thead>
                    <tr>
                        <th style="min-width:200px">Student Name</th>
                        <th style="min-width:190px">Reading Materials</th>
                        <th style="min-width:190px">Assessments</th>
                        <th style="min-width:180px">Overall Progress</th>
                        <th style="min-width:150px">Latest Activity</th>
                        <th style="min-width:90px;text-align:center">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($enrolledStudents as $es): 
                    $initials = strtoupper(substr($es['full_name'], 0, 2));
                ?>
                    <tr class="student-row" data-status="<?= $es['status_key'] ?>">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div class="avatar-sm" style="background:#e0e7ff;color:#4338ca;font-weight:700"><?= $initials ?></div>
                                <div>
                                    <strong class="student-name" style="font-size:13px;color:var(--text);display:block"><?= htmlspecialchars($es['full_name']) ?></strong>
                                    <small class="student-email text-muted"><?= htmlspecialchars($es['email']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;font-size:11px">
                                <span class="badge <?= $es['finished_topics_count'] === $es['total_topics_count'] && $es['total_topics_count'] > 0 ? 'badge-green' : ($es['finished_topics_count'] > 0 ? 'badge-blue' : 'badge-gray') ?>" style="font-size:10px;padding:2px 7px">
                                    <i class="fas <?= $es['finished_topics_count'] === $es['total_topics_count'] && $es['total_topics_count'] > 0 ? 'fa-check-double' : 'fa-book-open' ?>"></i>
                                    <?= $es['finished_topics_count'] ?> / <?= $es['total_topics_count'] ?> Topics
                                </span>
                                <span style="font-weight:700;color:var(--text);font-size:11px"><?= $es['read_avg_pct'] ?>%</span>
                            </div>
                            <div style="height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden;margin-bottom:6px">
                                <div style="height:100%;width:<?= min(100, max(0, $es['read_avg_pct'])) ?>%;background:<?= $es['read_avg_pct'] >= 90 ? '#10b981' : ($es['read_avg_pct'] > 0 ? '#3b82f6' : '#cbd5e1') ?>;border-radius:99px;transition:width 0.3s"></div>
                            </div>
                            <div style="display:flex;gap:4px;flex-wrap:wrap;font-size:10px">
                                <span style="background:#ecfdf5;color:#065f46;padding:1px 6px;border-radius:4px;border:1px solid #a7f3d0;font-weight:600" title="Finished Reading (90-100%)">
                                    <i class="fas fa-check" style="font-size:8px"></i> <?= $es['finished_topics_count'] ?> Done
                                </span>
                                <?php if ($es['reading_topics_count'] > 0): ?>
                                <span style="background:#eff6ff;color:#1e40af;padding:1px 6px;border-radius:4px;border:1px solid #bfdbfe;font-weight:600" title="Reading in Progress (1-89%)">
                                    <i class="fas fa-book-open" style="font-size:8px"></i> <?= $es['reading_topics_count'] ?> Reading
                                </span>
                                <?php endif; ?>
                                <?php if ($es['not_started_topics_count'] > 0): ?>
                                <span style="background:#f1f5f9;color:#475569;padding:1px 6px;border-radius:4px;border:1px solid #cbd5e1;font-weight:600" title="Not Started (0%)">
                                    <i class="fas fa-minus" style="font-size:8px"></i> <?= $es['not_started_topics_count'] ?> Not Started
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($es['total_assessments_count'] > 0): ?>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;font-size:11px">
                                <span class="badge <?= $es['submitted_assessments_count'] === $es['total_assessments_count'] ? 'badge-green' : ($es['submitted_assessments_count'] > 0 ? 'badge-purple' : 'badge-gray') ?>" style="font-size:10px;padding:2px 7px">
                                    <i class="fas fa-tasks"></i> <?= $es['submitted_assessments_count'] ?> / <?= $es['total_assessments_count'] ?> Submitted
                                </span>
                                <span style="font-weight:700;color:var(--text);font-size:11px"><?= $es['assess_avg_pct'] ?>%</span>
                            </div>
                            <div style="height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden;margin-bottom:6px">
                                <div style="height:100%;width:<?= min(100, max(0, $es['assess_avg_pct'])) ?>%;background:<?= $es['assess_avg_pct'] >= 100 ? '#10b981' : ($es['assess_avg_pct'] > 0 ? '#8b5cf6' : '#cbd5e1') ?>;border-radius:99px;transition:width 0.3s"></div>
                            </div>
                            <div style="display:flex;gap:4px;flex-wrap:wrap;font-size:10px">
                                <?php if ($es['graded_assessments_count'] > 0): ?>
                                <span style="background:#ecfdf5;color:#065f46;padding:1px 6px;border-radius:4px;border:1px solid #a7f3d0;font-weight:600" title="Graded Assessments">
                                    <i class="fas fa-check-circle" style="font-size:8px"></i> <?= $es['graded_assessments_count'] ?> Graded
                                </span>
                                <?php endif; ?>
                                <?php $pendingAssess = max(0, $es['submitted_assessments_count'] - $es['graded_assessments_count']); ?>
                                <?php if ($pendingAssess > 0): ?>
                                <span style="background:#eff6ff;color:#1e40af;padding:1px 6px;border-radius:4px;border:1px solid #bfdbfe;font-weight:600" title="Submitted (Awaiting Grading)">
                                    <i class="fas fa-clock" style="font-size:8px"></i> <?= $pendingAssess ?> Awaiting
                                </span>
                                <?php endif; ?>
                                <?php if ($es['unsubmitted_assessments_count'] > 0): ?>
                                <span style="background:#f1f5f9;color:#475569;padding:1px 6px;border-radius:4px;border:1px solid #cbd5e1;font-weight:600" title="Not Yet Submitted">
                                    <i class="fas fa-exclamation-circle" style="font-size:8px"></i> <?= $es['unsubmitted_assessments_count'] ?> Missing
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <span class="text-muted" style="font-size:12px">— None mapped —</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;font-size:11px">
                                <span style="font-weight:700;color:var(--text);font-size:12px"><?= $es['overall_pct'] ?>%</span>
                                <span class="badge <?= $es['status_badge'] ?>" style="font-size:10px;padding:2px 7px"><?= $es['status_label'] ?></span>
                            </div>
                            <div style="height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden;margin-bottom:4px">
                                <div style="height:100%;width:<?= min(100, max(0, $es['overall_pct'])) ?>%;background:<?= $es['bar_color'] ?>;border-radius:99px;transition:width 0.3s"></div>
                            </div>
                            <small class="text-muted" style="font-size:10px;display:block">
                                <?= $es['total_assessments_count'] > 0 ? 'Reading + Assessments' : 'Reading Modules' ?>
                            </small>
                        </td>
                        <td>
                            <span style="font-size:12px;color:var(--text2)"><?= $es['last_activity_formatted'] ?></span>
                        </td>
                        <td style="text-align:center">
                            <span class="badge badge-green" style="font-size:11px">
                                <i class="fas fa-check"></i> Enrolled
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add Topic Modal -->
<div class="modal-overlay" id="addTopicModal">
<div class="modal" style="max-width:700px">
<div class="modal-header"><span class="modal-title" id="topicModalTitle">Add Topic</span><button class="modal-close" onclick="closeModal('addTopicModal')">&times;</button></div>
<form method="POST" id="topicForm">
<input type="hidden" name="action" value="add_topic" id="topicAction">
<input type="hidden" name="topic_id" id="topicId">
<div class="modal-body">
    <div class="form-row">
        <div class="form-group"><label>Week Number</label><input type="number" name="week_number" id="tWeek" class="form-control" min="1" max="18" value="<?= count($topicsArr)+1 ?>" required></div>
        <div class="form-group"><label>Delivery Mode</label>
        <select name="delivery_mode" id="tMode" class="form-control" required>
            <option value="blended">Blended</option>
            <option value="face-to-face">Face-to-Face</option>
            <option value="online">Online</option>
            <option value="asynchronous">Asynchronous</option>
            <option value="synchronous">Synchronous</option>
        </select></div>
    </div>
    <div class="form-group"><label>Topic Title</label><input type="text" name="topic_title" id="tTitle" class="form-control" required placeholder="e.g. Introduction to Algorithms"></div>
    <div class="form-group"><label>Topic Description</label><textarea name="topic_description" id="tDesc" class="form-control" rows="3" placeholder="Brief description of the topic content..."></textarea></div>
    <div class="form-group"><label>Learning Outcomes</label><textarea name="learning_outcomes" id="tLO" class="form-control" rows="3" placeholder="At the end of this lesson, students should be able to..."></textarea></div>
    <div class="form-row">
        <div class="form-group"><label>Online Platform (if applicable)</label><input type="text" name="online_platform" id="tPlat" class="form-control" placeholder="Zoom, Google Meet, Moodle..."></div>
        <div class="form-group"><label>Assessment Type</label><input type="text" name="assessment_type" id="tAss" class="form-control" placeholder="Quiz, Activity, Recitation..."></div>
    </div>
    <div class="form-group"><label>Resources / References</label><textarea name="resources" id="tRes" class="form-control" rows="2" placeholder="Textbooks, links, materials..."></textarea></div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('addTopicModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Topic</button>
</div>
</form></div></div>

<!-- Upload Lesson Modal -->
<div class="modal-overlay" id="lessonModal">
<div class="modal" style="max-width:560px">
<div class="modal-header"><span class="modal-title" id="lessonModalTitle">Upload Lesson File</span><button class="modal-close" onclick="closeModal('lessonModal')">&times;</button></div>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="upload_lesson">
<input type="hidden" name="topic_id" id="lessonTopicId">
<div class="modal-body">
    <div class="form-group"><label>Lesson Title</label><input type="text" name="lesson_title" id="lessonTitle" class="form-control" required></div>
    <div class="form-group"><label>Description</label><textarea name="lesson_description" class="form-control" rows="2" placeholder="Optional note for students"></textarea></div>
    <div class="form-group"><label>Upload PDF / PPT / File</label><input type="file" name="lesson_file" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.zip"></div>
    <div class="form-group"><label>Or External Link</label><input type="url" name="external_url" class="form-control" placeholder="https://..."></div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('lessonModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Lesson</button>
</div>
</form></div></div>

<!-- Mark as Done modal -->
<div class="modal-overlay" id="doneModal">
<div class="modal">
    <div class="modal-header">
        <span class="modal-title"><i class="fas fa-check-circle"></i> Mark Topic as Done</span>
        <button class="modal-close" onclick="closeModal('doneModal')">&times;</button>
    </div>
    <div class="modal-body">
        <p style="font-size:14px;margin-bottom:14px">
            You're about to mark <strong id="doneTopicTitle"></strong> as completed.
        </p>
        <div class="form-group">
            <label>Notes <span style="color:var(--text3);font-weight:400">(optional — e.g. what was covered, follow-ups)</span></label>
            <textarea id="doneNotes" class="form-control" rows="3" placeholder="Anything worth remembering about how this week went..."></textarea>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('doneModal')">Cancel</button>
        <button type="button" class="btn btn-success" onclick="confirmMarkDone()"><i class="fas fa-check"></i> Mark as Complete</button>
    </div>
</div>
</div>

<!-- Request Deletion Modal -->
<div class="modal-overlay" id="requestDeleteModal">
<div class="modal" style="max-width:480px">
    <div class="modal-header">
        <span class="modal-title"><i class="fas fa-exclamation-triangle" style="color:#d97706;margin-right:6px"></i> Request Topic Deletion</span>
        <button class="modal-close" onclick="closeModal('requestDeleteModal')">&times;</button>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="request_delete_topic">
        <input type="hidden" name="topic_id" id="requestDeleteTopicId" value="">
        <div class="modal-body">
            <p style="font-size:13px;color:var(--text);margin-bottom:12px">
                You are requesting removal of <strong id="requestDeleteTopicTitle"></strong>. Curriculum changes require administrative approval.
            </p>
            <div class="form-group">
                <label>Reason for Deletion *</label>
                <textarea name="deletion_reason" class="form-control" rows="3" placeholder="e.g. Combined into another week, curriculum scope update, or redundant topic..." required></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('requestDeleteModal')">Cancel</button>
            <button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane"></i> Submit Request</button>
        </div>
    </form>
</div>
</div>

</div></div></div>
<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
function showTab(id,btn){
    document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
}
function openDeleteRequestModal(btn) {
    var topicId = btn.getAttribute('data-topic-id');
    var topicTitle = btn.getAttribute('data-topic-title') || '';
    document.getElementById('requestDeleteTopicId').value = topicId;
    document.getElementById('requestDeleteTopicTitle').textContent = topicTitle;
    openModal('requestDeleteModal');
}
function editTopic(t){
    document.getElementById('topicModalTitle').textContent='Edit Topic';
    document.getElementById('topicAction').value='edit_topic';
    document.getElementById('topicId').value=t.id;
    document.getElementById('tWeek').value=t.week_number;
    document.getElementById('tTitle').value=t.topic_title;
    document.getElementById('tDesc').value=t.topic_description;
    document.getElementById('tLO').value=t.learning_outcomes;
    document.getElementById('tMode').value=t.delivery_mode;
    document.getElementById('tPlat').value=t.online_platform;
    document.getElementById('tRes').value=t.resources;
    document.getElementById('tAss').value=t.assessment_type;
    openModal('addTopicModal');
}
function openLessonModal(topicId, title){
    document.getElementById('lessonTopicId').value = topicId;
    document.getElementById('lessonModalTitle').textContent = 'Upload Lesson File';
    document.getElementById('lessonTitle').value = title;
    openModal('lessonModal');
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));

function handleDoneClick(btn) {
    var topicId = btn.getAttribute('data-topic-id');
    var title = btn.getAttribute('data-topic-title') || '';
    var notes = btn.getAttribute('data-topic-notes') || '';
    openDoneModal(topicId, title, notes);
}

function handleLessonModalClick(btn) {
    var topicId = btn.getAttribute('data-topic-id');
    var title = btn.getAttribute('data-topic-title') || '';
    openLessonModal(topicId, title);
}

var pendingDoneTopicId = null;

// Opens the confirmation modal before marking a topic as done.
// existingNotes lets you re-open and edit notes if you undo + redo.
function openDoneModal(topicId, topicTitle, existingNotes) {
    pendingDoneTopicId = topicId;
    document.getElementById('doneTopicTitle').textContent = topicTitle;
    document.getElementById('doneNotes').value = existingNotes || '';
    openModal('doneModal');
}

function confirmMarkDone() {
    var notes = document.getElementById('doneNotes').value;
    submitStatus(pendingDoneTopicId, 'completed', notes);
    closeModal('doneModal');
}

// Used directly for "Undo" — no modal needed to un-mark a topic.
function updateStatus(topicId, status) {
    submitStatus(topicId, status, '');
}

function submitStatus(topicId, status, notes) {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=toggle_complete'
            + '&topic_id=' + encodeURIComponent(topicId)
            + '&status=' + encodeURIComponent(status)
            + '&notes=' + encodeURIComponent(notes || '')
    })
    .then(function(r){ return r.json(); })
    .then(function(d){ 
        if (d.success) {
            location.reload(); 
        } else {
            if (window.showCannotMarkDoneAlert) {
                window.showCannotMarkDoneAlert(d.message || 'Cannot mark topic as done.');
            } else {
                alert(d.message || 'Cannot mark topic as done.');
            }
        }
    });
}
var currentStudentStatusFilter = 'all';

function filterByStudentStatus(status, btn) {
    currentStudentStatusFilter = status;
    document.querySelectorAll('.student-filter-btn').forEach(function(b) {
        b.classList.remove('active');
        b.style.background = '';
        b.style.color = '';
        b.style.borderColor = '';
    });
    if (btn) {
        btn.classList.add('active');
        btn.style.background = 'var(--primary, #2563eb)';
        btn.style.color = '#fff';
        btn.style.borderColor = 'var(--primary, #2563eb)';
    }
    filterEnrolledStudents();
}

function filterEnrolledStudents() {
    var query = (document.getElementById('studentSearchInput').value || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#enrolledStudentsTable tbody tr.student-row');
    rows.forEach(function(row) {
        var name = (row.querySelector('.student-name') ? row.querySelector('.student-name').textContent : '').toLowerCase();
        var email = (row.querySelector('.student-email') ? row.querySelector('.student-email').textContent : '').toLowerCase();
        var rowStatus = row.getAttribute('data-status') || '';
        
        var matchesQuery = !query || name.includes(query) || email.includes(query);
        var matchesStatus = (currentStudentStatusFilter === 'all') || (rowStatus === currentStudentStatusFilter);
        
        if (matchesQuery && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function toggleWeekDetails(topicId) {
    var details = document.getElementById('week-details-' + topicId);
    var chevron = document.getElementById('chevron-' + topicId);
    var label = document.getElementById('label-' + topicId);
    if (!details) return;
    
    if (details.style.display === 'none' || details.style.display === '') {
        details.style.display = 'block';
        if (chevron) {
            chevron.classList.remove('fa-chevron-down');
            chevron.classList.add('fa-chevron-up');
        }
        if (label) label.textContent = label.textContent.replace('View', 'Hide');
    } else {
        details.style.display = 'none';
        if (chevron) {
            chevron.classList.remove('fa-chevron-up');
            chevron.classList.add('fa-chevron-down');
        }
        if (label) label.textContent = label.textContent.replace('Hide', 'View');
    }
}

function filterWeekStudents(topicId, type, btn) {
    var card = document.getElementById('week-details-' + topicId);
    if (card) {
        card.querySelectorAll('.week-filter-btn').forEach(function(b) {
            b.classList.remove('active');
            b.style.background = '';
            b.style.color = '';
        });
    }
    if (btn) {
        btn.classList.add('active');
        btn.style.background = 'var(--primary, #2563eb)';
        btn.style.color = '#fff';
    }

    var compSec = document.getElementById('week-completed-sec-' + topicId);
    var pendSec = document.getElementById('week-pending-sec-' + topicId);
    if (type === 'all') {
        if (compSec) compSec.style.display = 'block';
        if (pendSec) pendSec.style.display = 'block';
    } else if (type === 'completed') {
        if (compSec) compSec.style.display = 'block';
        if (pendSec) pendSec.style.display = 'none';
    } else if (type === 'pending') {
        if (compSec) compSec.style.display = 'none';
        if (pendSec) pendSec.style.display = 'block';
    }
}

function searchWeekStudentList(topicId, query) {
    query = (query || '').toLowerCase().trim();
    var container = document.getElementById('week-students-tables-' + topicId);
    if (!container) return;
    var rows = container.querySelectorAll('tr.week-stu-row');
    rows.forEach(function(row) {
        var name = row.getAttribute('data-name') || '';
        var email = row.getAttribute('data-email') || '';
        if (!query || name.includes(query) || email.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
</body></html>