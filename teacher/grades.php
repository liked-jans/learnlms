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
            SELECT e.student_id, u.full_name, u.email,
                   sub.id as submission_id, sub.score, sub.status as sub_status,
                   sub.submitted_at, sub.is_auto_graded,
                   (SELECT COUNT(*) FROM submission_answers sa JOIN assessment_questions aq ON sa.question_id=aq.id 
                    WHERE sa.submission_id=sub.id AND aq.question_type='essay' AND sa.is_correct IS NULL) as pending_essays
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            LEFT JOIN submissions sub ON sub.assessment_id = ? AND sub.student_id = e.student_id
            WHERE e.syllabus_id = ? AND e.status = 'enrolled'
            ORDER BY (sub.id IS NULL) ASC, u.full_name ASC
        ");
        $stmt2->bind_param('ii', $a['id'], $a['syllabus_id']);
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
        SELECT e.student_id, u.full_name, u.email,
               sub.id as submission_id, sub.score, sub.status as sub_status,
               sub.submitted_at, sub.is_auto_graded,
               (SELECT COUNT(*) FROM submission_answers sa JOIN assessment_questions aq ON sa.question_id=aq.id 
                WHERE sa.submission_id=sub.id AND aq.question_type='essay' AND sa.is_correct IS NULL) as pending_essays
        FROM enrollments e
        JOIN users u ON e.student_id = u.id
        LEFT JOIN submissions sub ON sub.assessment_id = ? AND sub.student_id = e.student_id
        WHERE e.syllabus_id = ? AND e.status = 'enrolled'
        ORDER BY (sub.id IS NULL) ASC, u.full_name ASC
    ");
    $stmt2->bind_param('ii', $assFilterInt, $assessment['syllabus_id']);
    $stmt2->execute();
    $res = $stmt2->get_result();
    $submissions = [];
    while ($r = $res->fetch_assoc()) { $submissions[] = $r; }
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
        <p style="color:var(--text3);font-size:13px;margin:2px 0 0">Review objective scores, evaluate essay responses, and publish student grades.</p>
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

<?php if (!$isAll && $assFilterInt && isset($assessment)): 
    $subCount = count(array_filter($submissions, fn($s) => !empty($s['submission_id'])));
    $notTakenCount = count($submissions) - $subCount;
    $isPastDue = !empty($assessment['due_date']) && (strtotime($assessment['due_date']) < time());
?>
<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap">
                    <span class="badge badge-blue"><?= htmlspecialchars($assessment['course_code']) ?></span>
                    <span class="badge badge-purple" style="text-transform:capitalize"><?= $assessment['type'] ?></span>
                    <span class="badge badge-gray"><?= $assessment['q_count'] ?> Questions</span>
                    <span class="badge badge-gray" style="background:#f1f5f9;color:#334155;font-weight:600">
                        <i class="fas fa-users" style="margin-right:4px"></i> <?= count($submissions) ?> Enrolled
                    </span>
                    <span class="badge badge-green" style="font-weight:600">
                        <i class="fas fa-check-circle" style="margin-right:4px"></i> <?= $subCount ?> Submitted
                    </span>
                    <?php if ($notTakenCount > 0): ?>
                        <span class="badge badge-gray" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;font-weight:600">
                            <i class="fas fa-clock" style="margin-right:4px"></i> <?= $notTakenCount ?> Not Taken
                        </span>
                    <?php endif; ?>
                </div>
                <h3 style="font-size:18px;font-weight:700;color:var(--text);margin:0"><?= htmlspecialchars($assessment['title']) ?></h3>
                <?php if (!empty($assessment['due_date'])): ?>
                    <div style="font-size:12px;color:var(--text3);margin-top:4px">
                        <i class="far fa-calendar-alt"></i> Due: <?= date('M d, Y g:i A', strtotime($assessment['due_date'])) ?>
                        <?php if ($isPastDue): ?>
                            <span class="badge badge-orange" style="font-size:10px;margin-left:6px">Deadline Passed</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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
<?php if (empty($submissions)): ?>
    <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text3)">No students enrolled in this syllabus yet.</td></tr>
<?php else: foreach ($submissions as $s): ?>
<tr>
    <td>
        <strong><?= htmlspecialchars($s['full_name']) ?></strong><br>
        <small class="text-muted"><?= htmlspecialchars($s['email']) ?></small>
    </td>
    <td>
        <?php if (!empty($s['submission_id'])): ?>
            <?= date('M d, Y g:i A', strtotime($s['submitted_at'])) ?>
        <?php else: ?>
            <span class="text-muted" style="font-size:13px;font-style:italic">Not submitted yet</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if (!empty($s['submission_id'])): ?>
            <strong style="color:var(--primary);font-size:15px"><?= number_format($s['score'] ?? 0, 1) ?></strong>
            <span style="color:var(--text3);font-size:12px">/ <?= number_format($assessment['max_score'], 1) ?> pts</span>
        <?php else: ?>
            <span style="color:var(--text3);font-size:14px;font-weight:600">&mdash;</span>
            <span style="color:var(--text3);font-size:12px">/ <?= number_format($assessment['max_score'], 1) ?> pts</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if (!empty($s['submission_id'])): ?>
            <span class="badge <?= $s['sub_status']==='graded' ? 'badge-green' : 'badge-orange' ?>" style="text-transform:capitalize">
                <?= htmlspecialchars($s['sub_status']) ?>
            </span>
            <?php if (!empty($s['pending_essays'])): ?>
                <br><span class="badge badge-orange" style="font-size:10px;margin-top:3px">
                    <i class="fas fa-pencil-alt"></i> <?= $s['pending_essays'] ?> Essay<?= $s['pending_essays'] > 1 ? 's' : '' ?> to Grade
                </span>
            <?php endif; ?>
        <?php else: ?>
            <?php if ($isPastDue): ?>
                <span class="badge badge-gray" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca">
                    <i class="fas fa-exclamation-circle"></i> Missing / Past Due
                </span>
            <?php else: ?>
                <span class="badge badge-gray" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca">
                    <i class="fas fa-clock"></i> Not Taken
                </span>
            <?php endif; ?>
        <?php endif; ?>
    </td>
    <td>
        <?php if (!empty($s['submission_id'])): ?>
            <a href="grade_submission.php?id=<?= $s['submission_id'] ?>" class="btn <?= !empty($s['pending_essays']) ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="display:inline-flex;align-items:center;gap:6px">
                <i class="fas <?= !empty($s['pending_essays']) ? 'fa-pencil-alt' : 'fa-edit' ?>"></i> 
                <?= !empty($s['pending_essays']) ? 'Grade Essays' : ($s['sub_status']==='graded' ? 'Review / Override' : 'Grade') ?>
            </a>
        <?php else: ?>
            <button class="btn btn-secondary btn-sm" disabled style="opacity:0.55;cursor:not-allowed;display:inline-flex;align-items:center;gap:6px">
                <i class="fas fa-hourglass-start"></i> Awaiting
            </button>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table></div></div>

<?php elseif ($isAll): ?>
<div class="card" style="margin-bottom:20px"><div class="card-body">
    <p style="font-size:13px;color:var(--text3);margin:0">Displaying all enrolled student rosters and submissions across your assessments.</p>
</div></div>

<?php foreach ($allData as $block):
    $a = $block['assessment'];
    $rows = $block['submissions'];
    $subCount = count(array_filter($rows, fn($r) => !empty($r['submission_id'])));
    $notTakenCount = count($rows) - $subCount;
    $isPastDue = !empty($a['due_date']) && (strtotime($a['due_date']) < time());
?>
<div class="card" style="margin-bottom:12px"><div class="card-body" style="padding:14px 20px">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span class="badge badge-blue"><?= htmlspecialchars($a['course_code']) ?></span>
                <strong><?= htmlspecialchars($a['title']) ?></strong>
                <span style="font-size:12px;color:var(--text3)">(Max: <?= number_format($a['max_score'], 1) ?> pts &bull; <?= $a['q_count'] ?> Questions)</span>
                <span class="badge badge-gray" style="font-size:11px;background:#f1f5f9;color:#334155;font-weight:600">
                    <i class="fas fa-users"></i> <?= count($rows) ?> Enrolled
                </span>
                <span class="badge badge-green" style="font-size:11px;font-weight:600">
                    <i class="fas fa-check-circle"></i> <?= $subCount ?> Submitted
                </span>
                <?php if ($notTakenCount > 0): ?>
                    <span class="badge badge-gray" style="font-size:11px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;font-weight:600">
                        <i class="fas fa-clock"></i> <?= $notTakenCount ?> Not Taken
                    </span>
                <?php endif; ?>
            </div>
            <?php if (!empty($a['due_date'])): ?>
                <div style="font-size:12px;color:var(--text3);margin-top:3px">
                    <i class="far fa-calendar-alt"></i> Due: <?= date('M d, Y g:i A', strtotime($a['due_date'])) ?>
                    <?php if ($isPastDue): ?>
                        <span class="badge badge-orange" style="font-size:10px;margin-left:4px">Deadline Passed</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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
    <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text3)">No students enrolled in this syllabus yet.</td></tr>
<?php else: foreach ($rows as $s): ?>
<tr>
    <td>
        <strong><?= htmlspecialchars($s['full_name']) ?></strong><br>
        <small class="text-muted"><?= htmlspecialchars($s['email']) ?></small>
    </td>
    <td>
        <?php if (!empty($s['submission_id'])): ?>
            <?= date('M d, Y g:i A', strtotime($s['submitted_at'])) ?>
        <?php else: ?>
            <span class="text-muted" style="font-size:13px;font-style:italic">Not submitted yet</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if (!empty($s['submission_id'])): ?>
            <strong style="color:var(--primary);font-size:15px"><?= number_format($s['score'] ?? 0, 1) ?></strong>
            <span style="color:var(--text3);font-size:12px">/ <?= number_format($a['max_score'], 1) ?> pts</span>
        <?php else: ?>
            <span style="color:var(--text3);font-size:14px;font-weight:600">&mdash;</span>
            <span style="color:var(--text3);font-size:12px">/ <?= number_format($a['max_score'], 1) ?> pts</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if (!empty($s['submission_id'])): ?>
            <span class="badge <?= $s['sub_status']==='graded' ? 'badge-green' : 'badge-orange' ?>" style="text-transform:capitalize">
                <?= htmlspecialchars($s['sub_status']) ?>
            </span>
            <?php if (!empty($s['pending_essays'])): ?>
                <br><span class="badge badge-orange" style="font-size:10px;margin-top:3px">
                    <i class="fas fa-pencil-alt"></i> <?= $s['pending_essays'] ?> Essay<?= $s['pending_essays'] > 1 ? 's' : '' ?> to Grade
                </span>
            <?php endif; ?>
        <?php else: ?>
            <?php if ($isPastDue): ?>
                <span class="badge badge-gray" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca">
                    <i class="fas fa-exclamation-circle"></i> Missing / Past Due
                </span>
            <?php else: ?>
                <span class="badge badge-gray" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca">
                    <i class="fas fa-clock"></i> Not Taken
                </span>
            <?php endif; ?>
        <?php endif; ?>
    </td>
    <td>
        <?php if (!empty($s['submission_id'])): ?>
            <a href="grade_submission.php?id=<?= $s['submission_id'] ?>" class="btn <?= !empty($s['pending_essays']) ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="display:inline-flex;align-items:center;gap:6px">
                <i class="fas <?= !empty($s['pending_essays']) ? 'fa-pencil-alt' : 'fa-edit' ?>"></i> 
                <?= !empty($s['pending_essays']) ? 'Grade Essays' : ($s['sub_status']==='graded' ? 'Review / Override' : 'Grade') ?>
            </a>
        <?php else: ?>
            <button class="btn btn-secondary btn-sm" disabled style="opacity:0.55;cursor:not-allowed;display:inline-flex;align-items:center;gap:6px">
                <i class="fas fa-hourglass-start"></i> Awaiting
            </button>
        <?php endif; ?>
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
