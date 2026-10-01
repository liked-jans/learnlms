<?php
require_once '../includes/config.php';
requireRole('student');
$pageTitle = 'My Assessments';
$stid = $_SESSION['user_id'];

$sylFilter = (int)($_GET['syl'] ?? 0);
$mySyllabi = $conn->query("
    SELECT s.*, c.course_code, c.course_name 
    FROM enrollments e 
    JOIN syllabi s ON e.syllabus_id = s.id 
    JOIN courses c ON s.course_id = c.id 
    WHERE e.student_id = $stid AND e.status = 'enrolled'
");
$sylArr = []; while($s = $mySyllabi->fetch_assoc()) $sylArr[] = $s;

$where = "e.student_id = $stid AND e.status = 'enrolled'";
if ($sylFilter) $where .= " AND a.syllabus_id = $sylFilter";

$assessments = $conn->query("
    SELECT a.*, c.course_code, c.course_name,
           (SELECT COUNT(*) FROM assessment_questions aq WHERE aq.assessment_id = a.id) as q_count,
           sub.id as sub_id, sub.score, sub.status as sub_status, sub.submitted_at, sub.feedback, sub.is_auto_graded
    FROM assessments a 
    JOIN enrollments e ON e.syllabus_id = a.syllabus_id 
    JOIN courses c ON c.id = (SELECT course_id FROM syllabi WHERE id = a.syllabus_id) 
    LEFT JOIN submissions sub ON sub.assessment_id = a.id AND sub.student_id = $stid 
    WHERE $where 
    GROUP BY a.id 
    ORDER BY a.due_date IS NULL, a.due_date ASC
");
$tc = ['quiz'=>'badge-green','assignment'=>'badge-blue','exam'=>'badge-red','project'=>'badge-orange','activity'=>'badge-purple'];
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">

<div class="page-header">
    <div class="page-header-left">
        <h2>My Assessments & Quizzes</h2>
        <p style="color:var(--text3);font-size:13px;margin:2px 0 0">Take interactive quizzes, exams, and activities assigned by your instructors.</p>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:12px">
            <select name="syl" class="form-control" style="width:320px" onchange="this.form.submit()">
                <option value="">All Courses</option>
                <?php foreach($sylArr as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $sylFilter==$s['id']?'selected':'' ?>>
                        <?= htmlspecialchars($s['course_code'].' - '.$s['course_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<div class="card"><div class="table-wrap"><table>
<thead>
    <tr>
        <th>Assessment</th>
        <th>Course</th>
        <th>Type</th>
        <th>Questions</th>
        <th>Due Date</th>
        <th>Score</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
</thead>
<tbody>
<?php if ($assessments->num_rows === 0): ?>
    <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text3)">No assessments available for your enrolled courses yet.</td></tr>
<?php else: while($a = $assessments->fetch_assoc()):
    $isOverdue = $a['due_date'] && strtotime($a['due_date']) < time() && !$a['sub_id'];
?>
<tr>
    <td>
        <strong><?= safeHtml($a['title']) ?></strong>
        <?php if($a['description']): ?><br><small class="text-muted"><?= safeHtml(substr($a['description'],0,60)) ?></small><?php endif; ?>
    </td>
    <td><span class="badge badge-blue"><?= htmlspecialchars($a['course_code']) ?></span></td>
    <td><span class="badge <?= $tc[$a['type']] ?? 'badge-gray' ?>" style="text-transform:capitalize"><?= $a['type'] ?></span></td>
    <td>
        <span style="font-size:12px;font-weight:600">
            <i class="fas fa-list-ol" style="color:var(--primary);margin-right:3px"></i> <?= $a['q_count'] ?> item<?= $a['q_count'] != 1 ? 's' : '' ?>
        </span>
    </td>
    <td>
        <?php if($a['due_date']): ?>
            <span style="<?= $isOverdue?'color:var(--danger);font-weight:600':'' ?>"><?= date('M d, Y', strtotime($a['due_date'])) ?></span>
        <?php else: ?>
            <span class="text-muted">No deadline</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if ($a['sub_id'] && $a['sub_status'] === 'graded' && $a['score'] !== null): ?>
            <strong style="color:var(--primary);font-size:14px"><?= (float)$a['score'] ?></strong> / <?= (float)($a['max_score'] ?? 0) ?>
        <?php elseif ($a['sub_id'] && $a['score'] !== null): ?>
            <strong style="color:#d97706;font-size:13px"><?= (float)$a['score'] ?></strong> <small class="text-muted">(Partial)</small>
        <?php elseif ($a['sub_id']): ?>
            <span class="badge badge-orange"><i class="fas fa-clock"></i> Under Review</span>
        <?php else: ?>
            <span class="text-muted"><?= (float)($a['max_score'] ?? 0) ?> pts</span>
        <?php endif; ?>
    </td>
    <td>
        <?php 
        if ($a['sub_id']) {
            $ss = ['submitted'=>'badge-orange','graded'=>'badge-green','late'=>'badge-red'];
            echo '<span class="badge '.($ss[$a['sub_status']]??'badge-gray').'">'.ucfirst($a['sub_status']).'</span>';
        } elseif ($isOverdue) {
            echo '<span class="badge badge-red">Overdue</span>';
        } else {
            echo '<span class="badge badge-gray">Pending</span>';
        }
        ?>
    </td>
    <td>
        <?php if(!$a['sub_id'] && !$isOverdue): ?>
            <a href="take_assessment.php?id=<?= $a['id'] ?>" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:6px">
                <i class="fas fa-edit"></i> Take Assessment
            </a>
        <?php elseif($a['sub_id']): ?>
            <a href="take_assessment.php?id=<?= $a['id'] ?>" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:6px">
                <i class="fas fa-eye"></i> View Results
            </a>
        <?php else: ?>
            <span class="text-muted" style="font-size:12px">Closed</span>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table></div></div>

</div></div></div>
</body></html>
