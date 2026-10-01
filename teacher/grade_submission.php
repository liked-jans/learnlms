<?php
require_once '../includes/config.php';
requireRole('teacher');
$tid = $_SESSION['user_id'];
$subId = (int)($_GET['id'] ?? 0);

// Fetch submission and verify teacher owns the assessment
$stmt = $conn->prepare("
    SELECT sub.*, u.full_name, u.email, a.id as assessment_id, a.title as assessment_title, a.type as assessment_type, a.max_score,
           c.course_code, c.course_name
    FROM submissions sub
    JOIN users u ON sub.student_id = u.id
    JOIN assessments a ON sub.assessment_id = a.id
    JOIN syllabi s ON a.syllabus_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE sub.id = ? AND a.teacher_id = ?
");
$stmt->bind_param('ii', $subId, $tid);
$stmt->execute();
$sub = $stmt->get_result()->fetch_assoc();

if (!$sub) {
    setFlash('error', 'Submission not found or access denied.');
    redirect(BASE_URL . 'teacher/grades.php');
}

$pageTitle = 'Grade Submission: ' . $sub['full_name'];

// Handle POST Evaluation Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token expired. Please try again.');
        redirect(BASE_URL . 'teacher/grade_submission.php?id=' . $subId);
    }

    $overallFeedback = sanitize($_POST['overall_feedback'] ?? '');
    $pointsAwardedMap = $_POST['points_awarded'] ?? [];
    $feedbackMap = $_POST['item_feedback'] ?? [];

    $totalCalculated = 0.00;

    // Update each answer
    $stmtUpd = $conn->prepare("
        UPDATE submission_answers 
        SET points_awarded = ?, teacher_feedback = ?, is_correct = ?
        WHERE id = ? AND submission_id = ?
    ");

    foreach ($pointsAwardedMap as $ansId => $pts) {
        $ansId = (int)$ansId;
        $pts = max(0, (float)$pts);
        $totalCalculated += $pts;
        $fb = sanitize($feedbackMap[$ansId] ?? '');
        $isCorr = ($pts > 0) ? 1 : 0;

        $stmtUpd->bind_param('dsiii', $pts, $fb, $isCorr, $ansId, $subId);
        $stmtUpd->execute();
    }

    // Save submission final score and mark graded
    $stmtFinal = $conn->prepare("
        UPDATE submissions 
        SET score = ?, feedback = ?, status = 'graded', is_auto_graded = 0, graded_at = NOW()
        WHERE id = ?
    ");
    $stmtFinal->bind_param('dsi', $totalCalculated, $overallFeedback, $subId);
    $stmtFinal->execute();

    setFlash('success', 'Evaluation saved successfully! Final score: ' . number_format($totalCalculated, 1) . ' / ' . number_format($sub['max_score'], 1) . ' pts.');
    redirect(BASE_URL . 'teacher/grades.php?assessment=' . $sub['assessment_id']);
}

// Fetch all questions and student answers
$stmtAns = $conn->prepare("
    SELECT sa.*, q.question_text, q.question_type, q.points, q.options, q.correct_answer, q.explanation
    FROM submission_answers sa
    JOIN assessment_questions q ON sa.question_id = q.id
    WHERE sa.submission_id = ?
    ORDER BY q.sort_order ASC, q.id ASC
");
$stmtAns->bind_param('i', $subId);
$stmtAns->execute();
$answers = $stmtAns->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content" style="max-width:900px;margin:0 auto">

<!-- Breadcrumb -->
<div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text3);margin-bottom:16px">
    <a href="grades.php?assessment=<?= $sub['assessment_id'] ?>" style="color:var(--primary);text-decoration:none">
        <i class="fas fa-arrow-left" style="margin-right:4px"></i> Back to Submissions
    </a>
    <span>/</span>
    <span style="color:var(--text)"><?= htmlspecialchars($sub['course_code']) ?></span>
    <span>/</span>
    <span style="color:var(--text);font-weight:600"><?= safeHtml($sub['assessment_title']) ?></span>
</div>

<!-- Student Header Card -->
<div class="card" style="margin-bottom:24px;border:1px solid rgba(59,130,246,0.25);box-shadow:0 4px 14px rgba(0,0,0,0.03)">
    <div class="card-body" style="padding:22px 24px">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px">
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span class="badge badge-blue" style="font-weight:700"><?= htmlspecialchars($sub['course_code']) ?></span>
                    <span class="badge badge-purple" style="text-transform:uppercase;font-size:11px"><?= $sub['assessment_type'] ?></span>
                    <span class="badge <?= $sub['status']==='graded'?'badge-green':'badge-orange' ?>" style="text-transform:capitalize">
                        <?= $sub['status'] ?>
                    </span>
                </div>
                <h2 style="font-size:22px;font-weight:800;color:var(--text);margin:0 0 4px"><?= htmlspecialchars($sub['full_name']) ?></h2>
                <p style="font-size:13px;color:var(--text3);margin:0">
                    <?= htmlspecialchars($sub['email']) ?> &bull; Submitted on <?= date('M d, Y g:i A', strtotime($sub['submitted_at'])) ?>
                </p>
            </div>
            <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:12px 20px;text-align:center">
                <div style="font-size:11px;color:var(--text3);font-weight:700;text-transform:uppercase">Current Score</div>
                <div style="font-size:24px;font-weight:900;color:var(--primary)">
                    <?= number_format($sub['score'] ?? 0, 1) ?> <span style="font-size:13px;color:var(--text3);font-weight:600">/ <?= number_format($sub['max_score'], 1) ?> pts</span>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST">
    <?= csrfField() ?>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h3 style="font-size:16px;font-weight:700">Question Evaluation & Essay Grading</h3>
        <span style="font-size:12px;color:var(--text3)">
            <i class="fas fa-info-circle"></i> Review student answers below and assign declared points for essay responses.
        </span>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px">
        <?php foreach ($answers as $idx => $sa): 
            $num = $idx + 1;
            $qType = $sa['question_type'];
            $ptsMax = (float)$sa['points'];
            $isEssay = ($qType === 'essay');
        ?>
        <div class="card" style="border:1px solid <?= $isEssay ? '#f59e0b' : ($sa['is_correct'] ? '#10b981' : '#ef4444') ?>;box-shadow:0 2px 6px rgba(0,0,0,0.02)">
            <div class="card-body" style="padding:20px">
                <!-- Question Title Bar with DECLARED POINTS -->
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:12px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:var(--text);color:#fff;font-size:12px;font-weight:700">
                            <?= $num ?>
                        </span>
                        <strong style="font-size:13px">Question <?= $num ?></strong>
                        
                        <!-- Prominent DECLARED POINTS BADGE -->
                        <span class="badge" style="background:#2563eb;color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px">
                            <i class="fas fa-star" style="font-size:10px"></i> Declared: <?= $ptsMax ?> Point<?= $ptsMax != 1 ? 's' : '' ?><?= $isEssay ? ' for this Essay' : '' ?>
                        </span>

                        <span class="badge badge-gray" style="font-size:11px;text-transform:capitalize">
                            <?= str_replace('_', ' ', $qType) ?>
                        </span>
                    </div>
                    <div>
                        <?php if ($isEssay): ?>
                            <span class="badge badge-orange" style="font-size:11px;font-weight:700">
                                <i class="fas fa-user-edit"></i> Manual Teacher Grading Required
                            </span>
                        <?php elseif ($sa['is_correct']): ?>
                            <span class="badge badge-green" style="font-size:11px;font-weight:700">
                                <i class="fas fa-check"></i> Correct (+<?= (float)$sa['points_awarded'] ?> pts)
                            </span>
                        <?php else: ?>
                            <span class="badge badge-red" style="font-size:11px;font-weight:700">
                                <i class="fas fa-times"></i> Incorrect (0 pts)
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Question Prompt -->
                <div style="font-size:14px;font-weight:600;color:var(--text);line-height:1.5;margin-bottom:14px">
                    <?= formatMultilineText($sa['question_text']) ?>
                </div>

                <!-- Student Answer Box -->
                <div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:14px">
                    <div style="font-size:11px;color:var(--text3);font-weight:700;text-transform:uppercase;margin-bottom:6px">
                        Student's Response:
                    </div>
                    <div style="font-size:13.5px;color:var(--text);font-weight:<?= $isEssay ? '400' : '600' ?>;line-height:1.6">
                        <?php if ($qType === 'multiple_choice'): 
                            $ansText = getAssessmentOptionDisplay($sa['options'], $sa['student_answer']);
                            echo $ansText ? safeHtml($ansText) : '<em class="text-muted">No choice selected</em>';
                        elseif ($qType === 'true_false'):
                            echo safeHtml($sa['student_answer'] ?: 'No choice selected');
                        else:
                            echo formatMultilineText($sa['student_answer'] ?: 'No response provided by student.');
                        endif; ?>
                    </div>
                </div>

                <!-- Correct Answer reference for objective questions -->
                <?php if (!$isEssay && !$sa['is_correct']): ?>
                    <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:6px;padding:8px 12px;font-size:12px;color:#065f46;margin-bottom:14px">
                        <strong><i class="fas fa-check-circle"></i> Correct Answer Reference:</strong>
                        <?php if ($qType === 'multiple_choice'): 
                            echo safeHtml(getAssessmentOptionDisplay($sa['options'], $sa['correct_answer']));
                        else:
                            echo safeHtml($sa['correct_answer']);
                        endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Rubric Guidance for Essay -->
                <?php if ($isEssay && !empty($sa['explanation'])): ?>
                    <div style="background:#fffbeb;border:1px solid #fef3c7;border-radius:6px;padding:10px 14px;font-size:12px;color:#92400e;margin-bottom:14px">
                        <strong><i class="fas fa-clipboard-check"></i> Rubric Guidance:</strong>
                        <div style="margin-top:2px;color:#78350f"><?= formatMultilineText($sa['explanation']) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Score Input & Feedback Row -->
                <div style="background:#fff;border-top:1px solid var(--border);padding-top:14px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                    <div style="width:220px">
                        <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px;color:var(--text)">
                            Score Awarded (Max: <?= $ptsMax ?> pts) *
                        </label>
                        <div style="display:flex;align-items:center;gap:6px">
                            <input type="number" name="points_awarded[<?= $sa['id'] ?>]" class="form-control" value="<?= (float)$sa['points_awarded'] ?>" min="0" max="<?= $ptsMax ?>" step="0.25" required style="font-weight:700;font-size:14px">
                            <span style="font-size:13px;color:var(--text3);font-weight:600">/ <?= $ptsMax ?></span>
                        </div>
                    </div>
                    <div style="flex:1;min-width:260px">
                        <label style="font-size:12px;font-weight:700;display:block;margin-bottom:4px;color:var(--text)">
                            Question Specific Feedback (optional)
                        </label>
                        <input type="text" name="item_feedback[<?= $sa['id'] ?>]" class="form-control" value="<?= htmlspecialchars($sa['teacher_feedback'] ?? '') ?>" placeholder="Feedback on this answer...">
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Overall Evaluation & Submit Bar -->
    <div class="card" style="margin-top:24px;border:1px solid rgba(59,130,246,0.3);background:#eff6ff">
        <div class="card-body" style="padding:22px">
            <div class="form-group" style="margin-bottom:16px">
                <label style="font-size:13px;font-weight:700;color:var(--primary)">
                    <i class="fas fa-comment-dots"></i> Overall Teacher Feedback & Remarks (visible to student)
                </label>
                <textarea name="overall_feedback" class="form-control" rows="3" placeholder="Provide encouraging remarks, areas for improvement, or overall impression..."><?= htmlspecialchars($sub['feedback'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
                <div style="font-size:12px;color:var(--text2)">
                    <i class="fas fa-check-double" style="color:var(--primary)"></i> Saving will finalize the grade, compute total score, and publish feedback to the student.
                </div>
                <div style="display:flex;gap:10px">
                    <a href="grades.php?assessment=<?= $sub['assessment_id'] ?>" class="btn btn-secondary btn-sm">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save" style="margin-right:6px"></i> Save & Publish Grade
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

</div></div></div>
</body></html>
