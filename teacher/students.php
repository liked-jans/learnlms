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
        deleteEnrollmentCascade($id);
        if (function_exists('logActivity')) {
            logActivity($tid, "Teacher unenrolled student enrollment ID {$id} and cleared syllabus progress", 'Enrollment');
        }
        setFlash('success','Student removed and syllabus progress cleared.');
    }
    redirect(BASE_URL.'teacher/students.php');
}

$sylFilter = (int)($_GET['syl'] ?? 0);
$mySyllabi = $conn->query("SELECT s.*,c.course_code,c.course_name FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.teacher_id=$tid ORDER BY c.course_name");
$sylArr=[]; while($s=$mySyllabi->fetch_assoc()) $sylArr[]=$s;

if ($sylFilter) {
    $totalTopicsCount = (int)($conn->query("SELECT COUNT(*) as c FROM syllabus_topics WHERE syllabus_id = $sylFilter")->fetch_assoc()['c'] ?? 0);
    $totalAssessmentsCount = (int)($conn->query("SELECT COUNT(*) as c FROM assessments WHERE syllabus_id = $sylFilter")->fetch_assoc()['c'] ?? 0);

    $sql = "
        SELECT e.*, u.full_name, u.email, u.username,
               
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
        WHERE e.syllabus_id = ?
        ORDER BY u.full_name ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $sylFilter, $sylFilter, $sylFilter);
    $stmt->execute();
    $rawEnrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $enrollmentsList = [];
    foreach ($rawEnrollments as $r) {
        $tot = $totalTopicsCount;
        $fin = (int)$r['finished_topics'];
        $read = (int)$r['reading_topics'];
        $notStarted = max(0, $tot - $fin - $read);
        $readAvgPct = $tot > 0 ? round((float)$r['total_read_pct_sum'] / $tot, 1) : 0.0;
        $readAvgPct = min(100.0, max(0.0, $readAvgPct));

        $totAssess = $totalAssessmentsCount;
        $subAssess = (int)$r['submitted_assessments'];
        $gradedAssess = (int)$r['graded_submissions'];
        $assessAvgPct = $totAssess > 0 ? round(($subAssess / $totAssess) * 100, 1) : 0.0;

        if ($totAssess > 0 && $tot > 0) {
            $overallPct = round(((float)$r['total_read_pct_sum'] + ($subAssess * 100)) / ($tot + $totAssess), 1);
        } elseif ($tot > 0) {
            $overallPct = $readAvgPct;
        } else {
            $overallPct = $assessAvgPct;
        }
        $overallPct = min(100.0, max(0.0, $overallPct));

        $isFullyCompleted = ($overallPct >= 100.0) || ($fin === $tot && ($totAssess === 0 || $subAssess === $totAssess) && $tot > 0);

        if ($isFullyCompleted) {
            $statusLabel = 'Completed';
            $statusBadge = 'badge-green';
            $barColor = '#10b981';
        } elseif ($overallPct > 0 || $fin > 0 || $read > 0 || $subAssess > 0) {
            $statusLabel = 'In Progress';
            $statusBadge = 'badge-blue';
            $barColor = '#3b82f6';
        } else {
            $statusLabel = 'Not Started';
            $statusBadge = 'badge-gray';
            $barColor = '#cbd5e1';
        }

        $r['total_topics_count'] = $tot;
        $r['finished_topics_count'] = $fin;
        $r['reading_topics_count'] = $read;
        $r['not_started_topics_count'] = $notStarted;
        $r['read_avg_pct'] = $readAvgPct;

        $r['total_assessments_count'] = $totAssess;
        $r['submitted_assessments_count'] = $subAssess;
        $r['graded_assessments_count'] = $gradedAssess;
        $r['assess_avg_pct'] = $assessAvgPct;

        $r['overall_pct'] = $overallPct;
        $r['status_label'] = $statusLabel;
        $r['status_badge'] = $statusBadge;
        $r['bar_color'] = $barColor;

        $enrollmentsList[] = $r;
    }
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
    <div class="page-header-left"><h2>My Students</h2><p>Manage student enrollments and track learning progress</p></div>
    <div style="display:flex;gap:8px;align-items:center">
        <?php if ($sylFilter): ?>
        <a href="syllabus_edit.php?id=<?= $sylFilter ?>" class="btn btn-secondary"><i class="fas fa-chart-line"></i> Detailed Syllabus Analytics</a>
        <button class="btn btn-primary" onclick="openModal('enrollModal')"><i class="fas fa-user-plus"></i> Enroll Student</button>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-bottom:20px"><div class="card-body" style="padding:14px 20px">
<form method="GET" style="display:flex;gap:12px">
    <select name="syl" class="form-control" style="width:350px" onchange="this.form.submit()">
        <option value="">-- Select a Syllabus --</option>
        <?php foreach($sylArr as $s): ?><option value="<?= $s['id'] ?>" <?= $sylFilter==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['course_code'].' - '.$s['course_name']) ?></option><?php endforeach; ?>
    </select>
</form>
</div></div>
<?php if ($sylFilter && isset($enrollmentsList)): ?>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Student</th><th>Email</th><th style="min-width:240px">Overall Progress</th><th>Enrolled</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($enrollmentsList as $e): 
    $isTarget = (isset($_GET['student_id']) && (int)$_GET['student_id'] === (int)$e['student_id']);
?>
<tr id="student-<?= $e['student_id'] ?>" style="<?= $isTarget ? 'background:#ecfdf5;box-shadow:inset 4px 0 0 #10b981;transition:all 0.4s ease;' : '' ?>">
    <td><div style="display:flex;align-items:center;gap:8px"><div class="avatar-sm"><?= strtoupper(substr($e['full_name'],0,2)) ?></div><strong><?= htmlspecialchars($e['full_name']) ?></strong><?php if($isTarget): ?> <span class="badge badge-green" style="font-size:10px;margin-left:4px"><i class="fas fa-check"></i> Selected</span><?php endif; ?></div></td>
    <td><?= htmlspecialchars($e['email']) ?></td>
    <td style="min-width:240px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;font-size:11px">
            <span style="font-weight:700;color:var(--text);font-size:12px"><?= $e['overall_pct'] ?>%</span>
            <span class="badge <?= $e['status_badge'] ?>" style="font-size:10px;padding:2px 7px"><?= $e['status_label'] ?></span>
        </div>
        <div style="height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden;margin-bottom:6px">
            <div style="height:100%;width:<?= min(100, max(0, $e['overall_pct'])) ?>%;background:<?= $e['bar_color'] ?>;border-radius:99px;transition:width 0.3s"></div>
        </div>
        <div style="display:flex;gap:4px;flex-wrap:wrap;font-size:10px">
            <span style="background:#ecfdf5;color:#065f46;padding:1px 6px;border-radius:4px;border:1px solid #a7f3d0;font-weight:600" title="Reading Modules Progress">
                <i class="fas fa-book-open" style="font-size:8px"></i> <?= $e['finished_topics_count'] ?>/<?= $e['total_topics_count'] ?> Read (<?= $e['read_avg_pct'] ?>%)
            </span>
            <?php if ($e['total_assessments_count'] > 0): ?>
            <span style="background:#f3e8ff;color:#6b21a8;padding:1px 6px;border-radius:4px;border:1px solid #d8b4fe;font-weight:600" title="Assessment Tasks Progress">
                <i class="fas fa-tasks" style="font-size:8px"></i> <?= $e['submitted_assessments_count'] ?>/<?= $e['total_assessments_count'] ?> Tasks (<?= $e['assess_avg_pct'] ?>%)
            </span>
            <?php endif; ?>
        </div>
    </td>
    <td><?= date('M d, Y', strtotime($e['enrolled_at'])) ?></td>
    <td><span class="badge <?= $e['status']==='enrolled'?'badge-green':'badge-gray' ?>"><?= $e['status'] ?></span></td>
    <td><form method="POST" onsubmit="return confirm('Remove student?')"><input type="hidden" name="action" value="unenroll"><input type="hidden" name="id" value="<?= $e['id'] ?>"><button class="btn btn-danger btn-sm"><i class="fas fa-user-minus"></i></button></form></td>
</tr>
<?php endforeach; ?>
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

document.addEventListener('DOMContentLoaded', function() {
    var targetStudentId = <?= (int)($_GET['student_id'] ?? 0) ?>;
    if (targetStudentId > 0) {
        var row = document.getElementById('student-' + targetStudentId);
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    } else if (window.location.hash) {
        var el = document.querySelector(window.location.hash);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>
</body></html>