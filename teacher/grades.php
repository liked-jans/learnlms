<?php
require_once '../includes/config.php';
requireRole('teacher');
$pageTitle = 'Grades & Submissions';
$tid = $_SESSION['user_id'];

$assFilter = $_GET['assessment'] ?? '';
$isAll = ($assFilter === 'all');
$assFilterInt = (int)$assFilter;

$assessment = null;
$submissions = null;
$allData = [];

if ($isAll) {
    $stmtList = $conn->prepare("
        SELECT a.*, c.course_code, c.course_name,
               (SELECT COUNT(*) FROM assessment_questions WHERE assessment_id = a.id) as q_count
        FROM assessments a 
        LEFT JOIN syllabi s ON a.syllabus_id = s.id 
        LEFT JOIN courses c ON s.course_id = c.id 
        WHERE a.teacher_id = ? 
        ORDER BY a.created_at DESC
    ");
    $stmtList->bind_param('i', $tid);
    $stmtList->execute();
    $assessmentsList = $stmtList->get_result();

    while ($a = $assessmentsList->fetch_assoc()) {
        $stmt2 = $conn->prepare("
            SELECT sub.*, u.full_name, u.email,
                   (SELECT COUNT(*) FROM submission_answers sa JOIN assessment_questions aq ON sa.question_id=aq.id WHERE sa.submission_id=sub.id AND aq.question_type='essay' AND sa.is_correct IS NULL) as pending_essays
            FROM submissions sub 
            JOIN users u ON sub.student_id = u.id 
            WHERE sub.assessment_id = ? 
            ORDER BY u.full_name ASC
        ");
        $stmt2->bind_param('i', $a['id']);
        $stmt2->execute();
        $res = $stmt2->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        $allData[] = ['assessment' => $a, 'submissions' => $rows];
    }
} elseif ($assFilterInt) {
    $stmt = $conn->prepare("
        SELECT a.*, c.course_code, c.course_name,
               (SELECT COUNT(*) FROM assessment_questions WHERE assessment_id = a.id) as q_count
        FROM assessments a 
        LEFT JOIN syllabi s ON a.syllabus_id = s.id 
        LEFT JOIN courses c ON s.course_id = c.id 
        WHERE a.id = ? AND a.teacher_id = ?
    ");
    $stmt->bind_param('ii', $assFilterInt, $tid);
    $stmt->execute();
    $assessment = $stmt->get_result()->fetch_assoc();
    if (!$assessment) redirect(BASE_URL . 'teacher/grades.php');

    $stmt2 = $conn->prepare("
        SELECT sub.*, u.full_name, u.email,
               (SELECT COUNT(*) FROM submission_answers sa JOIN assessment_questions aq ON sa.question_id=aq.id WHERE sa.submission_id=sub.id AND aq.question_type='essay' AND sa.is_correct IS NULL) as pending_essays
        FROM submissions sub 
        JOIN users u ON sub.student_id = u.id 
        WHERE sub.assessment_id = ? 
        ORDER BY u.full_name ASC
    ");
    $stmt2->bind_param('i', $assFilterInt);
    $stmt2->execute();
    $submissions = $stmt2->get_result();
}

$stmtMy = $conn->prepare("
    SELECT a.*, c.course_code 
    FROM assessments a 
    LEFT JOIN syllabi s ON a.syllabus_id = s.id 
    LEFT JOIN courses c ON s.course_id = c.id 
    WHERE a.teacher_id = ? 
    ORDER BY a.created_at DESC
");
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
    <div class="page-header-left">
        <h2>Grades & Evaluations</h2>
        <p style="color:var(--text3);font-size:13px;margin:2px 0 0">Review auto-graded objective scores, evaluate essay responses, and publish student grades.</p>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <select name="assessment" class="form-control" style="width:380px" onchange="this.form.submit()">
                <option value="">-- Select an Assessment --</option>
                <option value="all" <?= $isAll ? 'selected' : '' ?>>-- All Assessments --</option>
                <?php while($a = $myAssessments->fetch_assoc()): ?>
                    <option value="<?= $a['id'] ?>" <?= (!$isAll && $assFilterInt == $a['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a['course_code'].' - '.$a['title']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>
</div>

<?php if (!$isAll && $assFilterInt && isset($assessment)): ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                    <span class="badge badge-blue"><?= htmlspecialchars($assessment['course_code']) ?></span>
                    <span class="badge badge-purple" style="text-transform:capitalize"><?= $assessment['type'] ?></span>
                    <span class="badge badge-gray"><?= $assessment['q_count'] ?> Questions</span>
                </div>
                <h3 style="font-size:18px;font-weight:700;color:var(--text);margin:0"><?= htmlspecialchars($assessment['title']) ?></h3>
            </div>
            <div style="display:flex;gap:10px">
                <a href="assessment_questions.php?id=<?= $assessment['id'] ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-list-ol"></i> View Question Pool
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card"><div class="table-wrap"><table>
<thead>
    <tr>
        <th>Student</th>
        <th>Submitted</th>
        <th>Score</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
</thead>
<tbody>
<?php if ($submissions->num_rows === 0): ?>
    <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text3)">No submissions received for this assessment yet.</td></tr>
<?php else: while($s = $submissions->fetch_assoc()): ?>
<tr>
    <td>
        <strong><?= htmlspecialchars($s['full_name']) ?></strong><br>
        <small class="text-muted"><?= htmlspecialchars($s['email']) ?></small>
    </td>
    <td><?= date('M d, Y g:i A', strtotime($s['submitted_at'])) ?></td>
    <td>
        <strong style="color:var(--primary);font-size:15px"><?= number_format($s['score'] ?? 0, 1) ?></strong>
        <span style="color:var(--text3);font-size:12px">/ <?= number_format($assessment['max_score'], 1) ?> pts</span>
    </td>
    <td>
        <span class="badge <?= $s['status']==='graded' ? 'badge-green' : 'badge-orange' ?>" style="text-transform:capitalize">
            <?= $s['status'] ?>
        </span>
        <?php if (!empty($s['is_auto_graded'])): ?>
            <br><span class="badge badge-gray" style="font-size:10px;margin-top:3px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0">
                <i class="fas fa-robot"></i> Auto-Graded
            </span>
        <?php endif; ?>
        <?php if (!empty($s['pending_essays'])): ?>
            <br><span class="badge badge-orange" style="font-size:10px;margin-top:3px">
                <i class="fas fa-pencil-alt"></i> <?= $s['pending_essays'] ?> Essay<?= $s['pending_essays'] > 1 ? 's' : '' ?> to Grade
            </span>
        <?php endif; ?>
    </td>
    <td>
        <a href="grade_submission.php?id=<?= $s['id'] ?>" class="btn <?= !empty($s['pending_essays']) ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="display:inline-flex;align-items:center;gap:6px">
            <i class="fas <?= !empty($s['pending_essays']) ? 'fa-pencil-alt' : 'fa-edit' ?>"></i> 
            <?= !empty($s['pending_essays']) ? 'Grade Essays' : ($s['status']==='graded' ? 'Review / Override' : 'Grade') ?>
        </a>
    </td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table></div></div>

<?php elseif ($isAll): ?>
<div class="card" style="margin-bottom:20px"><div class="card-body">
    <p style="font-size:13px;color:var(--text3);margin:0">Displaying all submissions across your assessments.</p>
</div></div>

<?php foreach ($allData as $block):
    $a = $block['assessment'];
    $rows = $block['submissions'];
?>
<div class="card" style="margin-bottom:12px"><div class="card-body" style="padding:14px 20px">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <div>
            <span class="badge badge-blue"><?= htmlspecialchars($a['course_code']) ?></span>
            <strong style="margin-left:8px"><?= htmlspecialchars($a['title']) ?></strong>
            <span style="font-size:12px;color:var(--text3);margin-left:8px">(Max: <?= number_format($a['max_score'], 1) ?> pts &bull; <?= $a['q_count'] ?> Questions)</span>
        </div>
        <a href="grades.php?assessment=<?= $a['id'] ?>" class="btn btn-secondary btn-sm">Filter This</a>
    </div>
</div></div>

<div class="card" style="margin-bottom:24px"><div class="table-wrap"><table>
<thead>
    <tr>
        <th>Student</th>
        <th>Submitted</th>
        <th>Score</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
</thead>
<tbody>
<?php if (empty($rows)): ?>
    <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text3)">No submissions yet.</td></tr>
<?php else: foreach ($rows as $s): ?>
<tr>
    <td>
        <strong><?= htmlspecialchars($s['full_name']) ?></strong><br>
        <small class="text-muted"><?= htmlspecialchars($s['email']) ?></small>
    </td>
    <td><?= date('M d, Y g:i A', strtotime($s['submitted_at'])) ?></td>
    <td>
        <strong style="color:var(--primary);font-size:15px"><?= number_format($s['score'] ?? 0, 1) ?></strong>
        <span style="color:var(--text3);font-size:12px">/ <?= number_format($a['max_score'], 1) ?> pts</span>
    </td>
    <td>
        <span class="badge <?= $s['status']==='graded' ? 'badge-green' : 'badge-orange' ?>" style="text-transform:capitalize">
            <?= $s['status'] ?>
        </span>
        <?php if (!empty($s['is_auto_graded'])): ?>
            <br><span class="badge badge-gray" style="font-size:10px;margin-top:3px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0">
                <i class="fas fa-robot"></i> Auto-Graded
            </span>
        <?php endif; ?>
        <?php if (!empty($s['pending_essays'])): ?>
            <br><span class="badge badge-orange" style="font-size:10px;margin-top:3px">
                <i class="fas fa-pencil-alt"></i> <?= $s['pending_essays'] ?> Essay<?= $s['pending_essays'] > 1 ? 's' : '' ?> to Grade
            </span>
        <?php endif; ?>
    </td>
    <td>
        <a href="grade_submission.php?id=<?= $s['id'] ?>" class="btn <?= !empty($s['pending_essays']) ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="display:inline-flex;align-items:center;gap:6px">
            <i class="fas <?= !empty($s['pending_essays']) ? 'fa-pencil-alt' : 'fa-edit' ?>"></i> 
            <?= !empty($s['pending_essays']) ? 'Grade Essays' : ($s['status']==='graded' ? 'Review / Override' : 'Grade') ?>
        </a>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table></div></div>
<?php endforeach; ?>

<?php else: ?>
<div class="empty-state card"><div class="card-body">
    <i class="fas fa-star-half-alt" style="font-size:36px;color:var(--text3);margin-bottom:10px"></i>
    <h3 style="font-size:16px;font-weight:700">Select an Assessment</h3>
    <p style="font-size:13px;color:var(--text3)">Choose an assessment from the dropdown above to view student submissions and evaluate essays.</p>
</div></div>
<?php endif; ?>

</div></div></div>
</body></html>
